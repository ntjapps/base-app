<?php

use App\Features\DevSystem;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;

describe('DevSystem feature', function () {
    it('enables for testing environment', function () {
        $u = User::factory()->create();
        Config::set('app.debug', false);
        $f = new DevSystem;

        expect($f->resolve($u))->toBeTrue();
    });

    it('disables when not super, not debug, not testing', function () {
        $this->app->detectEnvironment(fn () => 'production');
        Config::set('app.debug', false);

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('allows')->with('hasSuperPermission', User::class)->andReturnFalse();

        $u = User::factory()->create();
        $f = new DevSystem;

        expect($f->resolve($u))->toBeFalse();

        $this->app->detectEnvironment(fn () => 'testing');
    });
});
