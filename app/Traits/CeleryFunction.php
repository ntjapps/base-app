<?php

namespace App\Traits;

use App\Exceptions\CommonCustomException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

trait CeleryFunction
{
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

        /** Check task lock */
        if (Cache::has($task.'.rabbitmq.lock') && $exclusive) {
            throw new CommonCustomException('Task already running');
        }

        /** Create task lock */
        if ($exclusive) {
            Cache::put($task.'.rabbitmq.lock', true, now()->addMinutes($timeout));
        }

        $id = Str::orderedUuid()->toString();
        $timeout = $timeout ?? Carbon::now()->addSeconds(config('services.rabbitmq.timeout'));

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
                Cache::forget($task.'.rabbitmq.lock');
            }
            throw new CommonCustomException('Failed to connect to RabbitMQ: '.$e->getMessage(), $e->getCode(), $e);
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
