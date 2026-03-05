<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Interfaces\GoQueues;
use App\Models\RouteAnalytics as RouteAnalyticsModel;
use App\Traits\GoWorkerFunction;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RouteAnalytics
{
    use GoWorkerFunction;

    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        try {
            $route = $request->route();
            $user = Auth::user() ?? Auth::guard('api')->user();
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $path = '/'.$request->path();
            $routeGroup = str_starts_with($path, '/api/') ? 'api' : 'web';
            $routeName = $route?->getName();

            if (
                stripos($path, 'health') !== false
                || ($routeName !== null && stripos($routeName, 'health') !== false)
            ) {
                return $response;
            }

            $payload = [
                'method' => strtoupper($request->method()),
                'path' => $path,
                'route_name' => $routeName,
                'route_group' => $routeGroup,
                'status_code' => $response->getStatusCode(),
                'duration_ms' => max($durationMs, 0),
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_authenticated' => $user !== null,
                'occurred_at' => now()->toISOString(),
            ];

            $natsEnabled = config('services.nats.enabled', true);
            $rabbitEnabled = config('services.rabbitmq.enabled', true);
            $useQueue = $natsEnabled || $rabbitEnabled;

            if ($useQueue) {
                $this->sendGoTask('route_analytics_log', $payload, GoQueues::LOGGER);
            } else {
                RouteAnalyticsModel::create($payload);
            }
        } catch (\Throwable $e) {
        }

        return $response;
    }
}
