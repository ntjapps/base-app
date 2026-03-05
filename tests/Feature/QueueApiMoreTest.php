<?php

use App\Interfaces\CentralCacheInterfaceClass;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

describe('Queue API additional coverage', function () {
    it('covers notification severity mapping, logs, callbacks, and cache clears', function () {
        $tokenRes = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('passport.client_credentials_rabbitmq_client.id'),
            'client_secret' => config('passport.client_credentials_rabbitmq_client.secret', Str::random(40)),
            'scope' => 'queue',
        ]);
        $tokenRes->assertStatus(200);
        $accessToken = $tokenRes->json('access_token');

        $user = User::factory()->create();

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-notification'), [
                'user_id' => $user->id,
                'message' => 'E',
                'level_name' => 'ERROR',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-notification'), [
                'user_id' => $user->id,
                'message' => 'W',
                'level' => '300',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-notification'), [
                'user_id' => $user->id,
                'message' => 'I',
                'lock_id' => 'lock-x',
            ])->assertStatus(200);

        Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock('lock-x'), true, now()->addMinute());
        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-notification'), [
                'user_id' => $user->id,
                'message' => 'I2',
                'lock_id' => 'lock-x',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-log'), [])->assertStatus(422);

        foreach (['debug', 'info', 'warning', 'error', 'critical'] as $level) {
            Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock('t-'.$level), true, now()->addMinute());
            $this->withHeader('Authorization', 'Bearer '.$accessToken)
                ->postJson(route('queue-send-log'), [
                    'message' => 'm',
                    'level' => $level,
                    'task_name' => 't-'.$level,
                ])->assertStatus(200);
        }

        Cache::put(CentralCacheInterfaceClass::keyRabbitmqLock('cb1'), true, now()->addMinute());
        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-send-callbacks'), [
                'callbacks_code' => 'unknown',
                'callbacks_payload' => json_encode(['x' => 1]),
                'task_name' => 'cb1',
            ])->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-clear-cache'), [])
            ->assertStatus(200);

        $this->withHeader('Authorization', 'Bearer '.$accessToken)
            ->postJson(route('queue-clear-permissions'), [])
            ->assertStatus(200);
    });
});
