<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Pennant\Feature;
use Laravel\Telescope\Telescope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
            Telescope::ignoreMigrations();
        }

        Passport::ignoreMigrations();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** Feature Penant */
        Feature::define('dev-system', fn (User $user) => match (true) {
            $user->hasPermissionTo(User::SUPER) => true,
            config('app.debug') => true,
            default => false,
        });
    }
}
