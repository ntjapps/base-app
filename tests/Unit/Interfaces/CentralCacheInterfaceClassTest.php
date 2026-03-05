<?php

use App\Interfaces\CentralCacheInterfaceClass;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Redis;
use Mockery as m;

describe('CentralCacheInterfaceClass', function () {
    it('caches main menu for a user', function () {
        Config::set('cache.default', 'array');
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('allows')->andReturnTrue();

        $menu = CentralCacheInterfaceClass::mainMenuCache($user);
        expect($menu)->toBeArray();
        expect(count($menu))->toBeGreaterThan(0);
    });

    it('remembers and forgets role by name', function () {
        Config::set('cache.default', 'array');
        $role = Role::factory()->create(['name' => 'qa-role']);

        $cached = CentralCacheInterfaceClass::rememberRoleCache('qa-role');
        expect($cached)->toBeInstanceOf(Role::class);
        expect($cached->name)->toBe('qa-role');

        CentralCacheInterfaceClass::forgetRoleCache('qa-role');
    });

    it('flushes permissions in non-redis mode', function () {
        Config::set('cache.default', 'array');
        Cache::put('x', 'y', 10);

        CentralCacheInterfaceClass::flushPermissions();
        expect(Cache::has('x'))->toBeTrue();
    });

    it('flushes permissions in redis mode without throwing', function () {
        Config::set('cache.default', 'redis');
        Config::set('cache.prefix', 'p:');
        Config::set('database.redis.options.prefix', 'rp:');

        $redis = m::mock();
        $redis->shouldReceive('keys')->andReturn(['rp:p:permission:a', 'rp:p:role:b']);
        $redis->shouldReceive('del')->andReturn(1);
        $redis->shouldReceive('getOption')->andReturn('rp:');

        Redis::shouldReceive('connection')->with('cache')->andReturn($redis);

        CentralCacheInterfaceClass::flushPermissions();
        expect(true)->toBeTrue();
    });

    it('forgets all role caches in redis mode', function () {
        Config::set('cache.default', 'redis');
        Config::set('cache.prefix', 'p:');
        $user = User::factory()->create();

        Redis::shouldReceive('keys')->andReturn(['p:'.Role::class.'-name-a']);
        Redis::shouldReceive('del')->andReturn(1);

        CentralCacheInterfaceClass::forgetAllRoleCache($user);
        expect(true)->toBeTrue();
    });
});
