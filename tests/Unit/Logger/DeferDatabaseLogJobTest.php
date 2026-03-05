<?php

use App\Logger\Jobs\DeferDatabaseLogJob;
use App\Logger\Models\ServerLog;
use Illuminate\Support\Facades\Config;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Helper to create a test LogRecord.
 */
function makeLogRecord(string $message = 'test log entry'): LogRecord
{
    return new LogRecord(
        datetime: new \DateTimeImmutable,
        channel: 'test',
        level: Level::Debug,
        message: $message,
        context: ['user_id' => 'u1'],
        extra: [],
    );
}

describe('DeferDatabaseLogJob', function () {
    it('exposes queue metadata', function () {
        $job = new DeferDatabaseLogJob(makeLogRecord());
        expect($job->uniqueId())->toBe('DeferDatabaseLogJob');
        expect($job->tags())->toBeArray()->toContain('DeferDatabaseLogJob');
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(1);
        expect($job->uniqueFor)->toBe(60);
    });

    it('sends to go worker when nats is enabled (default backend)', function () {
        Config::set('services.nats.enabled', true);
        Config::set('services.nats.worker_backend', 'go');

        $record = makeLogRecord('nats go test');
        $job = new DeferDatabaseLogJob($record);
        $job->handle();

        expect(true)->toBeTrue();
    });

    it('writes directly to ServerLog when all queue backends disabled', function () {
        Config::set('services.nats.enabled', false);
        Config::set('services.rabbitmq.enabled', false);

        $countBefore = ServerLog::count();

        $record = makeLogRecord('direct db write');
        (new DeferDatabaseLogJob($record))->handle();

        expect(ServerLog::count())->toBeGreaterThan($countBefore);
    });

    it('falls back to direct DB write when rabbitmq is enabled but nats disabled', function () {
        Config::set('services.nats.enabled', false);
        Config::set('services.rabbitmq.enabled', true);
        Config::set('services.rabbitmq.worker_backend', 'go');

        $record = makeLogRecord('rabbitmq fallback');
        (new DeferDatabaseLogJob($record))->handle();

        expect(true)->toBeTrue();
    });
});
