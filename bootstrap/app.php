<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Exceptions\OAuthServerException as ExceptionsOAuthServerException;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Log\LogLevel;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/app/healthcheck',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '192.168.0.0/16');
        $middleware->trustProxies(headers: Request::HEADER_X_FORWARDED_FOR);

        $middleware->web(append: [
            CreateFreshApiToken::class,
        ]);

        $middleware->api(prepend: [
            ThrottleRequestsWithRedis::class.':api',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        if (app()->bound('sentry')) {
            Integration::handles($exceptions);
        }

        $exceptions->render(function (OAuthServerException $e, Request $request) {
            $user = $request->user();
            Log::debug('OAuthServerException', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'userId' => $user?->id,
                'userName' => $user?->name,
            ]);

            return response()->json([
                'isAuth' => false,
            ], 200);
        });

        $exceptions->level(OAuthServerException::class, LogLevel::WARNING);

        $exceptions->render(function (ExceptionsOAuthServerException $e, Request $request) {
            $user = $request->user();
            Log::debug('OAuthServerException', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'userId' => $user?->id,
                'userName' => $user?->name,
            ]);

            return response()->json([
                'isAuth' => false,
            ], 200);
        });

        $exceptions->level(ExceptionsOAuthServerException::class, LogLevel::WARNING);
    })->create();
