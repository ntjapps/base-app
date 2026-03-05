<?php

namespace App\Traits;

use App\Exceptions\CommonCustomException;
use App\Interfaces\CentralCacheInterfaceClass;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

trait CeleryFunction
{
    protected function isCeleryDryRun(): bool
    {
        return app()->environment('testing');
    }

    protected function createAmqpConnection(string $host, int $port, string $user, string $password, string $vhost)
    {
        return new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    }

    /**
     * Send task to celery
     */
    private function sendTask($task, $args = [], $queue = 'celery', $exclusive = false, $timeout = null): string
    {
        /** Validate $task */
        if (empty($task) || ! is_string($task)) {
            throw new CommonCustomException('Task name is required');
        }

        /** Validate $args */
        if (! is_array($args)) {
            throw new CommonCustomException('Task arguments must be an array');
        }

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

        $timeoutSeconds = $timeout ?? (int) config('services.rabbitmq.timeout', 3600);

        /** Check task lock */
        if (Cache::has(CentralCacheInterfaceClass::keyRabbitmqLock($task)) && $exclusive) {
            throw new CommonCustomException('Task already running');
        }

        /** Create task lock */
        if ($exclusive) {
            Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock($task), true, now()->addSeconds($timeoutSeconds));
        }

        $id = Str::orderedUuid()->toString();

        // Dry-run mode for tests or when RabbitMQ host is not configured: skip actual publish
        $dryRun = $this->isCeleryDryRun() || empty(config('services.rabbitmq.host'));
        if ($dryRun) {
            // Use syslog at info level for non-failure dry-run diagnostics
            syslog(LOG_INFO, 'Dry-run sendTask: '.json_encode(['task' => $task, 'id' => $id]));
            if ($exclusive) {
                // If exclusive lock was set earlier, keep it until timeout; worker won't run in tests
            }

            return $id;
        }

        try {
            $connection = $this->createAmqpConnection(
                (string) config('services.rabbitmq.host'),
                (int) config('services.rabbitmq.port'),
                (string) config('services.rabbitmq.user'),
                (string) config('services.rabbitmq.password'),
                (string) config('services.rabbitmq.vhost')
            );
            $channel = $connection->channel();
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

        /** Login to celery default queue */
        $channel->queue_declare($queue, false, true, false, false);

        /** Generate Payload Based on Celery Payload Message Protocol v2 */
        $headers = [
            'lang' => 'py',
            'task' => $task,
            'id' => $id,
            'root_id' => $id,
        ];
        $properties = [
            'correlation_id' => $id,
            'content_type' => 'application/json',
            'content_encoding' => 'utf-8',
        ];

        $message = new AMQPMessage(json_encode([
            array_values($args),
            (object) [],
            ['callbacks' => null, 'errbacks' => null, 'chain' => null, 'chord' => null],
        ]), $properties);
        $message->set('application_headers', new AMQPTable($headers));

        /** Send to RabbitMQ */
        $channel->basic_publish($message, 'celery', $queue);
        $channel->close();
        $connection->close();

        return $id;
    }
}
