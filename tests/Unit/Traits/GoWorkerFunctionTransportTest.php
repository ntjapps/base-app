<?php

use App\Models\TaskStatus;
use Illuminate\Support\Facades\Cache;

class FakeGoAmqpChannel
{
    public array $published = [];

    public function queue_declare(...$args): void {}

    public function basic_publish($message, $exchange, $routingKey): void
    {
        $this->published[] = [$exchange, $routingKey, $message];
    }

    public function close(): void {}
}

class FakeGoAmqpConnection
{
    public FakeGoAmqpChannel $ch;

    public function __construct()
    {
        $this->ch = new FakeGoAmqpChannel;
    }

    public function channel(): FakeGoAmqpChannel
    {
        return $this->ch;
    }

    public function close(): void {}
}

class GoWorkerFunctionTransportHarness
{
    use App\Traits\GoWorkerFunction;

    public ?FakeGoAmqpConnection $lastConnection = null;

    protected function isGoTaskDryRun(): bool
    {
        return false;
    }

    protected function createGoAmqpConnection(string $host, int $port, string $user, string $password, string $vhost)
    {
        $this->lastConnection = new FakeGoAmqpConnection;

        return $this->lastConnection;
    }

    public function callSendGoTask(string $task, array $payload = [], string $queue = 'celery'): string
    {
        return $this->sendGoTask($task, $payload, $queue);
    }

    public function callSendGoTaskWithOptions(string $task, mixed $payload = [], string $queue = 'celery', mixed $exclusive = false, mixed $timeout = null, mixed $notify = null): string
    {
        return $this->sendGoTask($task, $payload, $queue, $exclusive, $timeout, $notify);
    }
}

class FakeNatsServiceOk
{
    public array $calls = [];

    public function publishToQueue(string $subject, $taskObj, string $streamName): array
    {
        $this->calls[] = [$subject, $streamName];

        return ['stream' => $streamName, 'seq' => 1];
    }
}

class FakeNatsServiceFail
{
    public function publishToQueue(string $subject, $taskObj, string $streamName): array
    {
        throw new RuntimeException('nats down');
    }
}

class GoWorkerFunctionDryRunHarness
{
    use App\Traits\GoWorkerFunction;

    public function callSendGoTaskWithOptions(string $task, mixed $payload = [], string $queue = 'celery', mixed $exclusive = false, mixed $timeout = null, mixed $notify = null): string
    {
        return $this->sendGoTask($task, $payload, $queue, $exclusive, $timeout, $notify);
    }
}

class GoWorkerFunctionRabbitThrowHarness
{
    use App\Traits\GoWorkerFunction;

    protected function isGoTaskDryRun(): bool
    {
        return false;
    }

    protected function createGoAmqpConnection(string $host, int $port, string $user, string $password, string $vhost)
    {
        throw new Exception('rmq down', 2);
    }

    public function callSendGoTaskWithOptions(string $task, mixed $payload = [], string $queue = 'celery', mixed $exclusive = false, mixed $timeout = null): string
    {
        return $this->sendGoTask($task, $payload, $queue, $exclusive, $timeout);
    }
}

describe('GoWorkerFunction transports', function () {
    it('publishes via NATS when configured', function () {
        config()->set('services.nats.enabled', true);
        config()->set('services.nats.host', 'nats');
        config()->set('services.nats.stream_name', 'TASKS');
        config()->set('services.rabbitmq.host', '');

        $fake = new FakeNatsServiceOk;
        $this->app->instance(\App\Services\Nats\NatsService::class, $fake);

        $h = new GoWorkerFunctionTransportHarness;
        $id = $h->callSendGoTask('wa-inbound', ['user_id' => 'u1'], 'whatsapp');

        expect($id)->toBeString();
        expect($fake->calls)->not->toBeEmpty();
        expect(TaskStatus::whereKey($id)->exists())->toBeTrue();
    });

    it('falls back to RabbitMQ when NATS publish fails', function () {
        config()->set('services.nats.enabled', true);
        config()->set('services.nats.host', 'nats');
        config()->set('services.nats.stream_name', 'TASKS');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $this->app->instance(\App\Services\Nats\NatsService::class, new FakeNatsServiceFail);

        $h = new GoWorkerFunctionTransportHarness;
        $id = $h->callSendGoTask('wa-inbound', ['user_id' => 'u1'], 'whatsapp');

        expect($id)->toBeString();
        expect($h->lastConnection)->not->toBeNull();
        expect($h->lastConnection->ch->published)->not->toBeEmpty();
    });

    it('validates inputs', function () {
        $h = new GoWorkerFunctionTransportHarness;

        $h->callSendGoTask('', [], 'whatsapp');
    })->throws(\App\Exceptions\CommonCustomException::class);

    it('validates payload type', function () {
        $h = new GoWorkerFunctionTransportHarness;

        $h->callSendGoTaskWithOptions('wa-inbound', 'nope', 'whatsapp');
    })->throws(\App\Exceptions\CommonCustomException::class);

    it('validates queue name', function () {
        $h = new GoWorkerFunctionTransportHarness;
        $h->callSendGoTaskWithOptions('wa-inbound', [], '');
    })->throws(\App\Exceptions\CommonCustomException::class);

    it('validates exclusive flag', function () {
        $h = new GoWorkerFunctionTransportHarness;
        $h->callSendGoTaskWithOptions('wa-inbound', [], 'whatsapp', 'nope');
    })->throws(\App\Exceptions\CommonCustomException::class);

    it('validates timeout type', function () {
        $h = new GoWorkerFunctionTransportHarness;
        $h->callSendGoTaskWithOptions('wa-inbound', [], 'whatsapp', false, 'nope');
    })->throws(\App\Exceptions\CommonCustomException::class);

    it('uses provided idempotency key and removes it from payload', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $h = new GoWorkerFunctionTransportHarness;
        $id = $h->callSendGoTask('wa-inbound', ['idempotency_key' => 'fixed', 'user_id' => 'u1'], 'whatsapp');

        $ts = TaskStatus::findOrFail($id);
        expect($ts->idempotency_key)->toBe('fixed');
        expect($ts->payload)->not->toHaveKey('idempotency_key');
    });

    it('normalizes known boolean-like payload values', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $h = new GoWorkerFunctionTransportHarness;
        $id1 = $h->callSendGoTask('wa-inbound', ['type_create' => '1', 'user_id' => 'u1'], 'whatsapp');
        $id2 = $h->callSendGoTask('wa-inbound', ['type_create' => '0', 'user_id' => 'u1'], 'whatsapp');

        expect(TaskStatus::findOrFail($id1)->payload['type_create'])->toBeTrue();
        expect(TaskStatus::findOrFail($id2)->payload['type_create'])->toBeFalse();
    });

    it('normalizes boolean-like payload values from int, float, and unknown strings', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $h = new GoWorkerFunctionTransportHarness;
        $id1 = $h->callSendGoTask('wa-inbound', ['type_create' => 1, 'user_id' => 'u1', 'nonce' => 'n1'], 'whatsapp');
        $id2 = $h->callSendGoTask('wa-inbound', ['type_create' => 1.0, 'user_id' => 'u1', 'nonce' => 'n2'], 'whatsapp');
        $id3 = $h->callSendGoTask('wa-inbound', ['type_create' => 'yes', 'user_id' => 'u1', 'nonce' => 'n3'], 'whatsapp');
        $id4 = $h->callSendGoTask('wa-inbound', ['type_create' => 'maybe', 'user_id' => 'u1', 'nonce' => 'n4'], 'whatsapp');

        expect(TaskStatus::findOrFail($id1)->payload['type_create'])->toBeTrue();
        expect(TaskStatus::findOrFail($id2)->payload['type_create'])->toBeTrue();
        expect(TaskStatus::findOrFail($id3)->payload['type_create'])->toBeTrue();
        expect(TaskStatus::findOrFail($id4)->payload['type_create'])->toBeTrue();
    });

    it('rejects exclusive task when lock exists', function () {
        Cache::put(\App\Interfaces\CentralCacheInterfaceClass::keyRabbitmqLock('wa-inbound'), true, now()->addMinute());
        $h = new GoWorkerFunctionDryRunHarness;
        $h->callSendGoTaskWithOptions('wa-inbound', ['user_id' => 'u1'], 'whatsapp', true, 1);
    })->throws(\App\Exceptions\CommonCustomException::class, 'Task already running');

    it('survives task status persistence errors', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        \App\Models\TaskStatus::flushEventListeners();
        \App\Models\TaskStatus::creating(function () {
            throw new Exception('db down');
        });

        $h = new GoWorkerFunctionTransportHarness;
        $id = $h->callSendGoTask('wa-inbound', ['user_id' => 'u1'], 'whatsapp');
        expect($id)->toBeString();

        \App\Models\TaskStatus::flushEventListeners();
    });

    it('auto-builds notify sockudo payload for user id', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $h = new GoWorkerFunctionTransportHarness;
        $id = $h->callSendGoTaskWithOptions('wa-inbound', ['user_id' => 'u1'], 'whatsapp', false, null, null);

        $published = $h->lastConnection->ch->published;
        expect($published)->not->toBeEmpty();

        $msg = $published[0][2];
        $body = json_decode($msg->getBody(), true);

        expect($body['notify']['sockudo']['channel'])->toBe('private-App.Models.User.u1');
        expect($body['id'])->toBe($id);
    });

    it('keeps exclusive lock in dry-run mode', function () {
        config()->set('services.nats.enabled', true);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', '');

        $h = new GoWorkerFunctionDryRunHarness;
        $id = $h->callSendGoTaskWithOptions('wa-inbound', ['user_id' => 'u1'], 'whatsapp', true, 1);
        expect($id)->toBeString();
        expect(Cache::has(\App\Interfaces\CentralCacheInterfaceClass::keyRabbitmqLock('wa-inbound')))->toBeTrue();
    });

    it('releases lock and normalizes exception code on rabbit failure', function () {
        config()->set('services.nats.enabled', false);
        config()->set('services.nats.host', '');
        config()->set('services.rabbitmq.host', 'rmq');
        config()->set('services.rabbitmq.port', 5672);
        config()->set('services.rabbitmq.user', 'u');
        config()->set('services.rabbitmq.password', 'p');
        config()->set('services.rabbitmq.vhost', '/');

        $h = new GoWorkerFunctionRabbitThrowHarness;
        $thrown = null;
        try {
            $h->callSendGoTaskWithOptions('wa-inbound', ['user_id' => 'u1'], 'whatsapp', true, 1);
        } catch (\App\Exceptions\CommonCustomException $e) {
            $thrown = $e;
            expect($e->getMessage())->toContain('Failed to connect to RabbitMQ');
            expect($e->getCode())->toBe(422);
        }

        expect($thrown)->not->toBeNull();
        expect(Cache::has(\App\Interfaces\CentralCacheInterfaceClass::keyRabbitmqLock('wa-inbound')))->toBeFalse();
    });
});
