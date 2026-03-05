<?php

use Illuminate\Broadcasting\PrivateChannel;

describe('Events', function () {
    it('exposes broadcast channels and payloads', function () {
        $classes = [
            App\Events\DivisionCreated::class,
            App\Events\DivisionDeleted::class,
            App\Events\DivisionUpdated::class,
            App\Events\InstructionCreated::class,
            App\Events\InstructionDeleted::class,
            App\Events\InstructionUpdated::class,
            App\Events\OauthClientCreated::class,
            App\Events\OauthClientDeleted::class,
            App\Events\OauthClientUpdated::class,
            App\Events\RoleCreated::class,
            App\Events\RoleDeleted::class,
            App\Events\RoleUpdated::class,
            App\Events\UserCreated::class,
            App\Events\UserDeleted::class,
            App\Events\UserUpdated::class,
            App\Events\WhatsappTemplateCreated::class,
            App\Events\WhatsappTemplateDeleted::class,
            App\Events\WhatsappTemplateUpdated::class,
            App\Events\WhatsappMessageReceived::class,
        ];

        foreach ($classes as $class) {
            $e = match ($class) {
                App\Events\WhatsappTemplateCreated::class,
                App\Events\WhatsappTemplateUpdated::class => new $class(['id' => 't1']),
                App\Events\WhatsappTemplateDeleted::class => new $class('t1', 'name'),
                default => new $class,
            };
            $channels = $e->broadcastOn();
            expect($channels)->toBeArray();
            expect($channels[0])->toBeInstanceOf(PrivateChannel::class);

            if (method_exists($e, 'broadcastWith')) {
                expect($e->broadcastWith())->toBeArray();
            }
        }
    });
});
