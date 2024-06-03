<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\HorizonCheck;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        if (class_exists(HorizonCheck::class)) {
            Health::checks([
               HorizonCheck::new(), 
            ]);
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function (User $user) {
            return (config('app.debug') === true) ? true : Gate::forUser($user)->allows('hasSuperPermission', User::class);
        });
    }
}
