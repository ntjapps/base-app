<?php

namespace Tests\Unit\Traits;

use App\Exceptions\CommonCustomException;
use App\Interfaces\CentralCacheInterfaceClass;
use App\Traits\CeleryFunction;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;

class CeleryFunctionHarness
{
    use CeleryFunction;

    public function callSendTask(mixed $task, mixed $args = [], mixed $queue = 'celery', mixed $exclusive = false, mixed $timeout = null): string
    {
        $ref = new ReflectionClass($this);
        $m = $ref->getMethod('sendTask');
        $m->setAccessible(true);

        return $m->invoke($this, $task, $args, $queue, $exclusive, $timeout);
    }
}

class FakeAmqpChannel
{
    public array $published = [];

    public function queue_declare(...$args): void {}

    public function basic_publish($message, $exchange, $routingKey): void
    {
        $this->published[] = [$exchange, $routingKey, $message];
    }

    public function close(): void {}
}

class FakeAmqpConnection
{
    public FakeAmqpChannel $ch;

    public function __construct()
    {
        $this->ch = new FakeAmqpChannel;
    }

    public function channel(): FakeAmqpChannel
    {
        return $this->ch;
    }

    public function close(): void {}
}

class CeleryFunctionHarnessNoDryRun extends CeleryFunctionHarness
{
    public FakeAmqpConnection $lastConnection;

    protected function isCeleryDryRun(): bool
    {
        return false;
    }

    protected function createAmqpConnection(string $host, int $port, string $user, string $password, string $vhost)
    {
        $this->lastConnection = new FakeAmqpConnection;

        return $this->lastConnection;
    }
}

describe('CeleryFunction', function () {
    it('validates inputs', function () {
        $h = new CeleryFunctionHarness;

        $h->callSendTask('', []);
    })->throws(CommonCustomException::class);

    it('validates args and queue types', function () {
        $h = new CeleryFunctionHarness;

        $h->callSendTask('t', 'not-array');
    })->throws(CommonCustomException::class);

    it('rejects non-boolean exclusive', function () {
        $h = new CeleryFunctionHarness;

        $h->callSendTask('t', [], 'celery', 'nope');
    })->throws(CommonCustomException::class);

    it('rejects non-integer timeout', function () {
        $h = new CeleryFunctionHarness;

        $h->callSendTask('t', [], 'celery', false, '10');
    })->throws(CommonCustomException::class);

    it('supports exclusive lock in dry-run mode', function () {
        $h = new CeleryFunctionHarness;
        Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock('task-x'));

        $id = $h->callSendTask('task-x', [], 'celery', true, 1);
        expect($id)->toBeString();

        Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock('task-x'), true, now()->addMinutes(1));
        $h->callSendTask('task-x', [], 'celery', true, 1);
    })->throws(CommonCustomException::class);

    it('publishes message when not in dry-run mode', function () {
        config()->set('services.rabbitmq.host', 'host');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');
        config()->set('services.rabbitmq.timeout', 60);

        $h = new CeleryFunctionHarnessNoDryRun;
        $id = $h->callSendTask('task-a', ['x' => 1], 'celery', false, 10);
        expect($id)->toBeString();
        expect($h->lastConnection->ch->published)->not->toBeEmpty();
    });
});
