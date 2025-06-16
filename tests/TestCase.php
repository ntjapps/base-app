<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Prevent Redis connection
        if (class_exists('Illuminate\Support\Facades\Redis')) {
            Redis::shouldReceive('connection')->andReturnSelf();
            Redis::shouldReceive('get')->andReturn(null);
            Redis::shouldReceive('set')->andReturn(true);
            Redis::shouldReceive('del')->andReturn(true);
        }
        // Prevent RabbitMQ or AMQP connection
        if (! class_exists('PhpAmqpLib\\Connection\\AMQPStreamConnection')) {
            Mockery::mock('overload:PhpAmqpLib\\Connection\\AMQPStreamConnection')
                ->shouldReceive('channel')->andReturnSelf();
        }
        if (class_exists('Illuminate\Support\Facades\Http')) {
            Http::fake();
        }
    }
}
