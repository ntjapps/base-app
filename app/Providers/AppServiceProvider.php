<?php

namespace App\Providers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\InterfaceClass;
use App\Interfaces\PermissionConstants;
use App\Listeners\MigrationEventListener;
use App\Listeners\MigrationStartListener;
use App\Models\User;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Observers\WaMessageSentLogObserver;
use App\Observers\WaMessageWebhookLogObserver;
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
        // Telescope removed: no registration here.
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

        // Pulse removed: gate no longer defined

        /**
         * Implicitly grant "Super Admin" permission to bypass all gates
         * This works in the app by using gate-related functions like auth()->user->can() and @can()
         **/
        Gate::after(function (User $user) {
            return Cache::remember(
                CentralCacheInterfaceClass::keyPermissionHasPermissionTo(PermissionConstants::SUPER_ADMIN, $user->id),
                Carbon::now()->addYear(),
                fn () => $user->hasPermissionTo(PermissionConstants::SUPER_ADMIN)
            );
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
        WaMessageSentLog::observe(WaMessageSentLogObserver::class);
        WaMessageWebhookLog::observe(WaMessageWebhookLogObserver::class);

        /** Registering Rate Limits */
        RateLimiter::for('api', function (Request $request) {
            return $request->user()?->id !== null
                ? Limit::perMinute(1200)->by($request->ip())
                : Limit::perMinute(600)->by($request->ip());
        });
    }
}
