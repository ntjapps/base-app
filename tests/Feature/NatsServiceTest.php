<?php

use App\Services\Nats\NatsService;
use Basis\Nats\Client;

it('resolves NatsService from container and uses the Client singleton', function () {
    // Resolve the client singleton from the container
    $clientFromContainer = app(Client::class);

    // Resolving again should return the same instance (singleton)
    $clientFromContainerAgain = app(Client::class);
    expect($clientFromContainer)->toBe($clientFromContainerAgain);

    // Resolve the NatsService
    $natsService = app(NatsService::class);

    // Access the protected $client property on NatsService using reflection
    $ref = new ReflectionClass($natsService);
    $prop = $ref->getProperty('client');
    $prop->setAccessible(true);

    $clientInsideService = $prop->getValue($natsService);

    // The client inside the service should be identical to the container singleton
    expect($clientInsideService)->toBe($clientFromContainer);
});
