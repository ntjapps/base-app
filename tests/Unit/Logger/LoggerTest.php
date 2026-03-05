<?php

use App\Logger\DatabaseHandler;
use App\Logger\Jobs\DeferDatabaseLogJob;
use App\Logger\Jobs\DeferTelegramLogJob;
use App\Logger\Models\ServerLog;
use App\Logger\TelegramHandler;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;

describe('Logger', function () {
    it('dispatches database log job from handler', function () {
        $handler = new DatabaseHandler;
        $ref = new ReflectionClass($handler);
        $m = $ref->getMethod('write');
        $m->setAccessible(true);

        Bus::fake();
        $record = new LogRecord(new DateTimeImmutable, 'testing', Level::Info, 'hello', ['a' => 1]);
        $m->invoke($handler, $record);

        Bus::assertDispatched(DeferDatabaseLogJob::class);
    });

    it('fails quietly when dispatch fails', function () {
        $handler = new DatabaseHandler;
        $ref = new ReflectionClass($handler);
        $m = $ref->getMethod('write');
        $m->setAccessible(true);

        Bus::shouldReceive('dispatch')->andThrow(new RuntimeException('fail'));
        $record = new LogRecord(new DateTimeImmutable, 'testing', Level::Info, 'hello', ['a' => 1]);
        $m->invoke($handler, $record);

        expect(true)->toBeTrue();
    });

    it('dispatches telegram log chain', function () {
        $handler = new TelegramHandler;
        $ref = new ReflectionClass($handler);
        $m = $ref->getMethod('write');
        $m->setAccessible(true);

        Bus::fake();
        Config::set('telegram.group_id', 'g1');
        $record = new LogRecord(new DateTimeImmutable, 'testing', Level::Warning, 'boom', ['x' => 'y']);

        $m->invoke($handler, $record);
        Bus::assertChained([
            DeferTelegramLogJob::class,
            DeferTelegramLogJob::class,
        ]);
    });

    it('writes synchronously when no queue backend is enabled', function () {
        Config::set('services.nats.enabled', false);
        Config::set('services.rabbitmq.enabled', false);

        $job = new DeferDatabaseLogJob(new LogRecord(new DateTimeImmutable, 'testing', Level::Info, 'hello', ['k' => 'v']));
        $job->handle();

        $log = ServerLog::firstOrFail();
        expect($log->message)->toBe('hello');
        expect($log->channel)->toBe('testing');
    });

    it('uses go worker path when queue backend is enabled', function () {
        Config::set('services.nats.enabled', true);
        Config::set('services.rabbitmq.enabled', false);
        Config::set('services.nats.worker_backend', 'go');

        Log::shouldReceive('channel')->with('stdout')->andReturnSelf();
        Log::shouldReceive('info')->andReturnNull();

        $job = new DeferDatabaseLogJob(new LogRecord(new DateTimeImmutable, 'testing', Level::Info, 'hello', ['k' => 'v']));
        $job->handle();
        expect(true)->toBeTrue();
    });
});
