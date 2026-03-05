<?php

namespace App\Traits;

use App\Exceptions\CommonCustomException;
use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\GoQueues;
use App\Models\TaskStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

trait GoWorkerFunction
{
    protected function isGoTaskDryRun(): bool
    {
        return app()->environment('testing');
    }

    protected function createGoAmqpConnection(string $host, int $port, string $user, string $password, string $vhost)
    {
        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }

    /**
     * Send task to Go worker using the new task format.
     * Publishes tasks to NATS by default (falls back to RabbitMQ if NATS not available).
     */
    private function sendGoTask($task, $payload = [], $queue = GoQueues::CELERY, $exclusive = false, $timeout = null, $notify = null): string
    {
        /** Validate $task */
        if (empty($task) || ! is_string($task)) {
            throw new CommonCustomException('Task name is required');
        }

        /** Validate $payload */
        if (! is_array($payload)) {
            throw new CommonCustomException('Task payload must be an array');
        }

        // Normalize known fields to match Go worker JSON expectations.
        // Laravel's validator does not cast booleans; it may pass "0"/"1" strings through.
        $payload = $this->normalizeGoWorkerPayload($payload);

        // ---------------------------------------------------------------------
        // 🚀 DISCOVERY BEACON (Robustness Upgrade)
        // ---------------------------------------------------------------------
        // We plant a known key in Redis. The Go worker scans for "*:system:go_worker:beacon".
        // When it finds this key, it strips the suffix to reveal the EXACT prefix Laravel is using.
        // We set it for 24 hours so it persists even if queues are idle.
        Cache::put('system:go_worker:beacon', 'active', now()->addDay());
        // ---------------------------------------------------------------------

        /** Validate $queue */
        if (empty($queue) || ! is_string($queue)) {
            throw new CommonCustomException('Queue name is required');
        }

        /** Validate $exclusive */
        if (! is_bool($exclusive)) {
            throw new CommonCustomException('Exclusive must be a boolean');
        }

        /** Validate $timeout */
        if ($timeout !== null && ! is_int($timeout)) {
            throw new CommonCustomException('Timeout must be an integer');
        }

        /** Check task lock */
        if (Cache::has(CentralCacheInterfaceClass::keyRabbitmqLock($task)) && $exclusive) {
            throw new CommonCustomException('Task already running');
        }

        /** Create task lock */
        if ($exclusive) {
            Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock($task), true, now()->addMinutes($timeout ?? 60));
        }

        $id = Str::orderedUuid()->toString();

        /** Determine idempotency key: allow caller to supply, otherwise generate from task+payload */
        if (! empty($payload['idempotency_key']) && is_string($payload['idempotency_key'])) {
            $idempotencyKey = $payload['idempotency_key'];
            // remove from payload body to avoid duplication
            unset($payload['idempotency_key']);
        } else {
            // create a deterministic normalized JSON of payload
            try {
                $normalized = $this->normalizeForIdempotency($payload);
                $idempotencyKey = hash('sha256', $task.'|'.$normalized);
            } catch (\Exception $e) {
                // fallback to random uuid if normalization fails
                $idempotencyKey = 'gen:'.Str::orderedUuid()->toString();
            }
        }

        /** If caller provided a user_id in payload and didn't supply notify, create a Sockudo notify so Go worker can broadcast back to the user */
        if ($notify === null && ! empty($payload['user_id']) && is_string($payload['user_id'])) {
            $notify = [
                'sockudo' => [
                    'channel' => 'private-App.Models.User.'.$payload['user_id'],
                    'event' => 'notification',
                    'include_payload' => true,
                ],
            ];
        }

        /** Persist task status to database before publishing */
        $userId = null;
        if (method_exists($this, 'guard')) {
            // Try to get authenticated user
            $user = Auth::user() ?? Auth::guard('api')->user();
            $userId = $user?->id;
        }
        // Fallback: Check Auth facade directly if not using guard method (e.g. Controllers)
        if ($userId === null) {
            $user = Auth::user() ?? Auth::guard('api')->user();
            $userId = $user?->id;
        }

        /** Generate Payload Based on Go Worker Task Format */
        $taskObj = \App\Services\Tasks\GoWorkerTask::create(
            task: $task,
            payload: $payload,
            idempotencyKey: $idempotencyKey,
            id: $id,
            maxAttempts: 5,
            timeout: $timeout,
            notify: $notify,
            invokerId: $userId,
        );

        $properties = [
            'content_type' => 'application/json',
            'content_encoding' => 'utf-8',
            'delivery_mode' => 2, // persistent
        ];

        $message = new AMQPMessage($taskObj->toJson(), $properties);

        try {
            TaskStatus::create([
                'id' => $taskObj->getId(),
                'task_name' => $taskObj->getTask(),
                'idempotency_key' => $taskObj->getIdempotencyKey(),
                'queue' => $queue,
                'status' => 'queued',
                'payload' => $taskObj->getPayload(),
                'attempt' => $taskObj->getAttempt(),
                'max_attempts' => $taskObj->getMaxAttempts(),
                'queued_at' => now(),
                'user_id' => $userId,
            ]);
        } catch (\Exception $e) {
            // If persisting task status fails, log to the dedicated stdout channel so
            // we avoid using the application's logging pipeline which might cause a loop.
            try {
                Log::channel('stdout')->error('Failed to persist task status', [
                    'task_id' => $id,
                    'task' => $task,
                    'error' => $e->getMessage(),
                ]);
            } catch (\Throwable $logErr) {
                // As a last-resort fallback, emit to syslog so the operator can see it
                syslog(LOG_ERR, 'Failed to persist task status: '.json_encode([
                    'task_id' => $id,
                    'task' => $task,
                    'error' => $e->getMessage(),
                ]));
            }
        }

        // Decide which transport to use: prefer NATS, fallback to RabbitMQ
        $natsEnabled = config('services.nats.enabled', true);
        $natsHost = config('services.nats.host');
        $rabbitHost = config('services.rabbitmq.host');

        // Dry-run mode for tests or when neither broker is configured
        $dryRun = $this->isGoTaskDryRun() || (empty($natsHost) && empty($rabbitHost));

        if ($dryRun) {
            // Use stdout channel for non-failure dry-run diagnostics to avoid feedback loops
            try {
                Log::channel('stdout')->info('Dry-run sendGoTask', ['task' => $task, 'id' => $id]);
            } catch (\Throwable $logErr) {
                // Fallback to syslog only if stdout logging fails
                syslog(LOG_INFO, 'Dry-run sendGoTask: '.json_encode(['task' => $task, 'id' => $id]));
            }

            // If exclusive lock was set earlier, keep it until the timeout expires as real worker isn't invoked in tests
            return $id;
        }

        // If NATS is enabled and host is configured, publish to NATS by default
        if ($natsEnabled && ! empty($natsHost)) {
            try {
                try {
                    Log::channel('stdout')->info(sprintf('sendGoTask: publishing to NATS subject=%s id=%s', $queue, $id));
                } catch (\Throwable $logErr) {
                    // Fallback: if stdout logging fails, do not attempt to use app logger
                }

                // Compute stream and payload details for logging
                $streamName = config('services.nats.stream_name', 'TASKS');
                $payload = $taskObj->toJson();
                $payloadSize = strlen($payload);

                // Publish and measure duration; log ack/details for operator visibility
                $start = microtime(true);
                $ack = app(\App\Services\Nats\NatsService::class)->publishToQueue($queue, $taskObj, $streamName);
                $durationMs = (int) (1000 * (microtime(true) - $start));

                try {
                    Log::channel('stdout')->info(sprintf('sendGoTask: NATS publish succeeded subject=%s id=%s dur_ms=%d', $queue, $id, $durationMs), ['ack' => $ack, 'stream' => $streamName, 'payload_size' => $payloadSize]);
                } catch (\Throwable $logErr) {
                    // ignore logging failures
                }

                return $id;
            } catch (\Throwable $e) {
                // On publish failure, fall back to RabbitMQ and log to stdout channel
                try {
                    Log::channel('stdout')->error(sprintf('sendGoTask: NATS publish failed: %s; falling back to RabbitMQ', $e->getMessage()), ['exception' => $e]);
                } catch (\Throwable $logErr) {
                    // Fallback to syslog if necessary
                    syslog(LOG_ERR, sprintf('sendGoTask: NATS publish failed: %s; falling back to RabbitMQ', $e->getMessage()));
                }
                // fallthrough to RabbitMQ publish
            }
        }

        // Default/fallback: publish to RabbitMQ
        $payload = $taskObj->toJson();
        $payloadSize = strlen($payload);
        try {
            Log::channel('stdout')->info(sprintf('sendGoTask: publishing to RabbitMQ queue=%s id=%s', $queue, $id), ['payload_size' => $payloadSize]);
        } catch (\Throwable $logErr) {
            // Fallback to syslog if necessary
            syslog(LOG_INFO, sprintf('sendGoTask: publishing to RabbitMQ queue=%s id=%s payload_size=%d', $queue, $id, $payloadSize));
        }

        return $this->publishToRabbitMQ($id, $task, $message, $queue, $exclusive);
    }

    /**
     * Publish task to RabbitMQ.
     */
    private function publishToRabbitMQ(string $id, string $task, AMQPMessage $message, string $queue, bool $exclusive): string
    {
        try {
            $connection = $this->createGoAmqpConnection(
                (string) config('services.rabbitmq.host'),
                (int) config('services.rabbitmq.port'),
                (string) config('services.rabbitmq.user'),
                (string) config('services.rabbitmq.password'),
                (string) config('services.rabbitmq.vhost')
            );
            $channel = $connection->channel();

            /** Declare queue */
            $channel->queue_declare($queue, false, true, false, false);

            /** Send to RabbitMQ */
            $channel->basic_publish($message, '', $queue);
            $channel->close();
            $connection->close();

            return $id;
        } catch (\Exception $e) {
            if ($exclusive) {
                Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock($task));
            }
            // Ensure we don't propagate non-HTTP error codes (e.g. PHP error severity 2)
            $code = (int) $e->getCode();
            if ($code < 100 || $code > 599) {
                $code = 422;
            }
            throw new CommonCustomException('Failed to connect to RabbitMQ: '.$e->getMessage(), $code, $e);
        }
    }

    /**
     * Normalize payload for deterministic idempotency key generation.
     * This will sort arrays/objects by keys recursively and return compact JSON.
     */
    private function normalizeForIdempotency($data): string
    {
        $normalized = $this->recursiveKeySort($data);
        $json = json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new \RuntimeException('Failed to normalize payload for idempotency');
        }

        return $json;
    }

    private function recursiveKeySort($data)
    {
        if (is_array($data)) {
            // associative array? sort by keys
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);
            if ($isAssoc) {
                ksort($data);
            }
            foreach ($data as $k => $v) {
                $data[$k] = $this->recursiveKeySort($v);
            }

            return $data;
        }
        if (is_object($data)) {
            $arr = (array) $data;
            ksort($arr);
            foreach ($arr as $k => $v) {
                $arr[$k] = $this->recursiveKeySort($v);
            }

            return $arr;
        }

        return $data;
    }

    /**
     * Normalize payload values to be compatible with strict Go JSON decoding.
     */
    private function normalizeGoWorkerPayload(array $payload): array
    {
        if (array_key_exists('type_create', $payload)) {
            $payload['type_create'] = $this->normalizeBooleanValue($payload['type_create']);
        }

        return $payload;
    }

    private function normalizeBooleanValue(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        // JSON numbers may arrive as int/float.
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_float($value)) {
            return (int) $value === 1;
        }

        // Most common case: "0"/"1" from request validation.
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($normalized, ['0', 'false', 'no', 'off', ''], true)) {
                return false;
            }
        }

        return (bool) $value;
    }
}
