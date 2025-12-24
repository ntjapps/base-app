<?php

namespace App\Traits;

use App\Exceptions\CommonCustomException;
use App\Interfaces\CentralCacheInterfaceClass;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

trait GoWorkerFunction
{
    /**
     * Send task to Go worker using the new task format
     */
    private function sendGoTask($task, $payload = [], $queue = 'celery', $exclusive = false, $timeout = null, $notify = null): string
    {
        /** Validate $task */
        if (empty($task) || ! is_string($task)) {
            throw new CommonCustomException('Task name is required');
        }

        /** Validate $payload */
        if (! is_array($payload)) {
            throw new CommonCustomException('Task payload must be an array');
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

        /** Check task lock */
        if (Cache::has(CentralCacheInterfaceClass::keyRabbitmqLock($task)) && $exclusive) {
            throw new CommonCustomException('Task already running');
        }

        /** Create task lock */
        if ($exclusive) {
            Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock($task), true, now()->addMinutes($timeout ?? 60));
        }

        $id = Str::orderedUuid()->toString();

        try {
            $connection = new AMQPStreamConnection(
                config('services.rabbitmq.host'),
                config('services.rabbitmq.port'),
                config('services.rabbitmq.user'),
                config('services.rabbitmq.password'),
                config('services.rabbitmq.vhost')
            );
            $channel = $connection->channel();
        } catch (\Exception $e) {
            if ($exclusive) {
                Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock($task));
            }
            throw new CommonCustomException('Failed to connect to RabbitMQ: '.$e->getMessage(), $e->getCode(), $e);
        }

        /** Declare queue */
        $channel->queue_declare($queue, false, true, false, false);

        /** Generate Payload Based on Go Worker Task Format */
        $taskPayload = [
            'version' => '1.0',
            'id' => $id,
            'task' => $task,
            'payload' => $payload,
            'created_at' => Carbon::now()->toIso8601String(),
            'attempt' => 0,
            'max_attempts' => 5,
        ];

        if ($timeout !== null) {
            $taskPayload['timeout_seconds'] = $timeout;
        }

        if ($notify !== null) {
            $taskPayload['notify'] = $notify;
        }

        $properties = [
            'content_type' => 'application/json',
            'content_encoding' => 'utf-8',
            'delivery_mode' => 2, // persistent
        ];

        $message = new AMQPMessage(json_encode($taskPayload), $properties);

        /** Send to RabbitMQ */
        $channel->basic_publish($message, '', $queue);
        $channel->close();
        $connection->close();

        return $id;
    }
}
