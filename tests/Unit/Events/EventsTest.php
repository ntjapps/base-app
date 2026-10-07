<?php

use App\Events\DivisionCreated;
use App\Events\DivisionDeleted;
use App\Events\DivisionUpdated;
use App\Events\InstructionCreated;
use App\Events\InstructionDeleted;
use App\Events\InstructionUpdated;
use App\Events\OauthClientCreated;
use App\Events\OauthClientDeleted;
use App\Events\OauthClientUpdated;
use App\Events\RoleCreated;
use App\Events\RoleDeleted;
use App\Events\RoleUpdated;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Events\WhatsappMessageReceived;
use App\Events\WhatsappTemplateCreated;
use App\Events\WhatsappTemplateDeleted;
use App\Events\WhatsappTemplateUpdated;
use Illuminate\Broadcasting\PrivateChannel;

describe('Events', function () {
    it('exposes broadcast channels and payloads', function () {
        $classes = [
            DivisionCreated::class,
            DivisionDeleted::class,
            DivisionUpdated::class,
            InstructionCreated::class,
            InstructionDeleted::class,
            InstructionUpdated::class,
            OauthClientCreated::class,
            OauthClientDeleted::class,
            OauthClientUpdated::class,
            RoleCreated::class,
            RoleDeleted::class,
            RoleUpdated::class,
            UserCreated::class,
            UserDeleted::class,
            UserUpdated::class,
            WhatsappTemplateCreated::class,
            WhatsappTemplateDeleted::class,
            WhatsappTemplateUpdated::class,
            WhatsappMessageReceived::class,
        ];

        foreach ($classes as $class) {
            $e = match ($class) {
                WhatsappTemplateCreated::class,
                WhatsappTemplateUpdated::class => new $class(['id' => 't1']),
                WhatsappTemplateDeleted::class => new $class('t1', 'name'),
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
