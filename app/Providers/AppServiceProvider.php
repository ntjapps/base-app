<?php

namespace App\Providers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\InterfaceClass;
use App\Interfaces\PermissionConstants;
use App\Listeners\MigrationEventListener;
use App\Listeners\MigrationStartListener;
use App\Models\User;
use App\Policies\UserPolicy;
use Basis\Nats\Client;
use Basis\Nats\Configuration;
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
use Illuminate\Support\Facades\Log;
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
        // Register Basis\Nats\Client as a singleton so the TCP connection is reused.
        $this->app->singleton(Client::class, function ($app) {
            $cfg = config('services.nats', []);

            // IMPORTANT: Do not call Laravel's Log facade from inside this singleton.
            // This client may be resolved while a Monolog handler is processing a log
            // record (e.g. the `database` handler dispatching a job). Logging here would
            // re-enter Monolog and trigger its infinite-loop protection.
            $logToStderr = static function (string $message): void {
                try {
                    error_log($message);
                } catch (\Throwable $ignored) {
                    // Swallow all errors - logging must never break service registration.
                }
            };

            $host = $cfg['host'] ?? 'nats';
            $port = (int) ($cfg['port'] ?? 4222);
            $timeout = $cfg['timeout'] ?? 5;

            // THE FIX: Pre-resolve DNS to IPv4 to bypass library's slow DNS handling
            // gethostbyname() forces immediate IPv4 lookup using OS resolver
            $resolvedIp = gethostbyname($host);

            // Safety check: if resolution fails, gethostbyname returns the hostname back
            if ($resolvedIp === $host && filter_var($host, FILTER_VALIDATE_IP) === false) {
                $logToStderr("NATS: Could not resolve hostname {$host} to an IP, using hostname");
            } else {
                $logToStderr("NATS: Resolved {$host} to {$resolvedIp}");
            }

            $configuration = new Configuration(
                host: $resolvedIp, // Pass resolved IP instead of hostname
                port: $port,
                user: $cfg['user'] ?? null,
                pass: $cfg['pass'] ?? null,
                timeout: $timeout,
            );

            // Apply delay settings if present
            $delayValue = $cfg['delay']['value'] ?? null;
            $delayMode = $cfg['delay']['mode'] ?? null;
            if ($delayValue !== null) {
                $modeConst = Configuration::DELAY_CONSTANT;
                if (is_string($delayMode)) {
                    switch (strtolower($delayMode)) {
                        case 'linear':
                            $modeConst = Configuration::DELAY_LINEAR;
                            break;
                        case 'exponential':
                            $modeConst = Configuration::DELAY_EXPONENTIAL;
                            break;
                        default:
                            $modeConst = Configuration::DELAY_CONSTANT;
                    }
                }

                $configuration->setDelay((float) $delayValue, $modeConst);
            }

            $client = new Client($configuration);

            // Connect immediately to keep the socket alive for the request duration
            if (method_exists($client, 'connect')) {
                try {
                    $client->connect();
                } catch (\Throwable $e) {
                    $logToStderr('NATS: Failed to connect on start: '.$e->getMessage());
                }
            }

            return $client;
        });

        // Register NatsService as a singleton to reuse connection state across calls
        $this->app->singleton(\App\Services\Nats\NatsService::class, function ($app) {
            return new \App\Services\Nats\NatsService(
                $app->make(Client::class)
            );
        });
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
            'queue' => 'Queue Callbacks',
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

        /** Registering Observers
         * Disabled: conversation threading is now handled by the Go service (main-site-go).
         * The Go worker creates and updates `wa_api_message_threads` for inbound and outbound messages.
         * Observers removed to prevent duplicate/contradictory thread creation.
         */

        /** Registering Rate Limits */
        RateLimiter::for('api', function (Request $request) {
            return $request->user()?->id !== null
                ? Limit::perMinute(1200)->by($request->ip())
                : Limit::perMinute(600)->by($request->ip());
        });
    }
}
