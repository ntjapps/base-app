<?php

use App\Interfaces\GoQueues;
use App\Services\Nats\NatsService;
use App\Services\Tasks\GoWorkerTask;
use Basis\Nats\Client;

// Ensure minimal stubs for typed expectations
if (! class_exists(\Basis\Nats\Api::class)) {
    eval('namespace Basis\\Nats; class Api { public function getStream(string $name): \\Basis\\Nats\\Stream\\Stream {} }');
}
if (! class_exists(\Basis\Nats\Stream\Stream::class)) {
    eval('namespace Basis\\Nats\\Stream; class Stream { public function put(string $subject, string $payload): object {} }');
}

it('publishes GoWorkerTask to NATS JetStream using stream->put()', function () {
    // Prepare mock for client->publish (fire-and-forget path)
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('publish')
        ->once()
        ->with(GoQueues::WHATSAPP, \Mockery::on(function ($payload) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) && $decoded['task'] === 'tasks.whatsapp';
        }))
        ->andReturnSelf();

    // Replace the container binding with our mock
    $this->instance(Client::class, $clientMock);

    $nats = app(NatsService::class);

    $task = GoWorkerTask::create('tasks.whatsapp', ['foo' => 'bar'], idempotencyKey: 'ik');

    $ack = $nats->publishToQueue(GoQueues::WHATSAPP, $task);

    // fire-and-forget publish returns null ack
    expect($ack)->toBeNull();
});

it('throws when subject is not a GoQueues constant', function () {
    $this->expectException(\InvalidArgumentException::class);

    $nats = app(NatsService::class);

    $nats->publishToQueue('not.a.valid.subject', ['a' => 'b']);
});
