<?php

namespace App\Providers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        /**
         * Passport Configuration
         */
        Passport::cookie('api_token_cookie');
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(Carbon::now()->addDays(1));
        Passport::refreshTokensExpireIn(Carbon::now()->addDays(30));
        Passport::personalAccessTokensExpireIn(Carbon::now()->addDays(7));

        Passport::useClientModel(\App\Models\PassportClient::class);
        Passport::usePersonalAccessClientModel(\App\Models\PassportPersonalAccessClient::class);

        /**
         * Implicitly grant "Super User" role with some limitation to policy
         * This works in the app by using gate-related functions like auth()->user->can() and @can()
         **/
        Gate::after(function ($user) {
            return $user->hasPermissionTo(User::SUPER);
        });
    }
}
