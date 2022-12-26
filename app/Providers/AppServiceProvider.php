<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** Override Sanctum Default Models to use UUIDS */
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
