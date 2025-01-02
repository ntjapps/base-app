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
            $permission = Cache::remember(Permission::class.'-ability-'.InterfaceClass::SUPER, Carbon::now()->addYear(), function () {
                return Permission::where('name', InterfaceClass::SUPER)->first();
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
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(InterfaceClass::getPassportAuthTokenLifetime());
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

        /** Registering Rate Limits */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perSecond(300)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-min', function (Request $request) {
            return Limit::perSecond(1)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api-secure', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
