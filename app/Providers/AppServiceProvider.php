<?php

namespace App\Providers;

use App\Interfaces\InterfaceClass;
use App\Listeners\MigrationEventListener;
use App\Listeners\MigrationStartListener;
use App\Models\Permission;
use App\Models\PermissionMenu;
use App\Models\PermissionPrivilege;
use App\Models\User;
use App\Observers\PermissionMenuObserver;
use App\Observers\PermissionPrivilegeObserver;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /** Register Telescope only in local environment */
        if ($this->app->environment('local')) {
            if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
                $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
                $this->app->register(TelescopeServiceProvider::class);

                Laravel\Telescope\Telescopenight();
            }
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
            $permission = Cache::remember(Permission::class.'-abilityType-'.PermissionPrivilege::class.'-ability-'.InterfaceClass::SUPER, Carbon::now()->addYear(), function () {
                return Permission::whereHas('ability', function ($query) {
                    $query->where('title', InterfaceClass::SUPER);
                })->where('ability_type', (string) PermissionPrivilege::class)->first();
            });

            $hasPermissionToCache = Cache::remember(Permission::class.'-hasPermissionTo-'.$permission->id.'-user-'.$user->id, Carbon::now()->addYear(), function () use ($user, $permission) {
                return $user->hasPermissionTo($permission);
            });

            return $hasPermissionToCache;
        });

        /**
         * Passport Configuration
         */
        Passport::cookie('api_token_cookie');
        Passport::tokensExpireIn(InterfaceClass::getPassportAuthTokenLifetime());
        Passport::refreshTokensExpireIn(InterfaceClass::getPassportRefreshTokenLifetime());
        Passport::personalAccessTokensExpireIn(InterfaceClass::getPassportTokenLifetime());

        Passport::useClientModel(\App\Models\Passport\Client::class);

        Passport::tokensCan([
            'rabbitmq' => 'Rabbitmq Access API for Queue Callbacks',
        ]);

        /** Register Broadcasting */
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:api']]);

        /** Register Events */
        Event::listen(MigrationsStarted::class, MigrationStartListener::class);
        Event::listen(MigrationStarted::class, MigrationStartListener::class);
        Event::listen(MigrationsEnded::class, MigrationEventListener::class);
        Event::listen(MigrationEnded::class, MigrationEventListener::class);

        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('laravelpassport', \SocialiteProviders\LaravelPassport\Provider::class);
        });

        /** Registering Observers */
        PermissionPrivilege::observe(PermissionPrivilegeObserver::class);
        PermissionMenu::observe(PermissionMenuObserver::class);

        /** Registering Rate Limits */
        RateLimiter::for('api', function (Request $request) {
            return $request->user()?->id !== null
                ? Limit::perMinute(1200)->by($request->ip())
                : Limit::perMinute(600)->by($request->ip());
        });
    }
}
