<?php

namespace App\Providers;

use App\Interfaces\InterfaceClass;
use App\Listeners\MigrationEventListener;
use App\Listeners\MigrationStartListener;
use App\Models\PassportAuthCode;
use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use App\Models\PassportRefreshToken;
use App\Models\PassportToken;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Policies\UserPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Telescope\Telescope;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** Register Telescope */
        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);

            Telescope::night();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /** Laravel strict exception */
        Model::preventSilentlyDiscardingAttributes(! $this->app->environment('production'));

        /** Register Policies */
        Gate::policy(User::class, UserPolicy::class);

        /** Pulse Gate */
        Gate::define('viewPulse', function (User $user) {
            return (config('app.debug') === true) ? true : Gate::forUser($user)->allows('hasSuperPermission', User::class);
        });

        /**
         * Implicitly grant "Super User" role with some limitation to policy
         * This works in the app by using gate-related functions like auth()->user->can() and @can()
         **/
        Gate::after(function (User $user) {
            return $user->hasPermissionTo(InterfaceClass::SUPER);
        });

        /**
         * Passport Configuration
         */
        Passport::cookie('api_token_cookie');
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(InterfaceClass::getPassportTokenLifetime());
        Passport::refreshTokensExpireIn(InterfaceClass::getPassportRefreshTokenLifetime());
        Passport::personalAccessTokensExpireIn(InterfaceClass::getPassportTokenLifetime());

        Passport::useTokenModel(PassportToken::class);
        Passport::useRefreshTokenModel(PassportRefreshToken::class);
        Passport::useAuthCodeModel(PassportAuthCode::class);
        Passport::useClientModel(PassportClient::class);
        Passport::usePersonalAccessClientModel(PassportPersonalAccessClient::class);

        /** Register Broadcasting */
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:api']]);

        /** Register Events */
        Event::listen(MigrationsStarted::class, MigrationStartListener::class);
        Event::listen(MigrationStarted::class, MigrationStartListener::class);
        Event::listen(MigrationsEnded::class, MigrationEventListener::class);
        Event::listen(MigrationEnded::class, MigrationEventListener::class);

        /** Registering Observers */
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);

        /** Registering Rate Limits */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-min', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-secure', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
