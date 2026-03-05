<?php

namespace App\Http\Controllers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\MenuItemClass;
use App\Logger\Models\ServerLog;
use App\Models\RouteAnalytics;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Monolog\Logger;

class ServerManController extends Controller
{
    use JsonResponse, LogContext;

    /**
     * GET request to view server logs layouts
     */
    public function serverLogs(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open server log', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Server Logs',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    public function routeAnalyticsPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open route analytics', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Route Analytics',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get server Logs from tables
     */
    public function getServerLogs(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User requesting server logs', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'date_start' => ['nullable', 'date', 'before_or_equal:date_end'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'log_level' => ['nullable', 'string'],
            'log_message' => ['nullable', 'string'],
            'log_extra' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('Server logs validation passed', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $data = ServerLog::when($validated['date_start'] ?? null, function ($query, $date_start) {
            return $query->where('created_at', '>=', Carbon::parse($date_start, 'Asia/Jakarta')->startOfDay());
        })->when($validated['date_end'] ?? null, function ($query, $date_end) {
            return $query->where('created_at', '<=', Carbon::parse($date_end, 'Asia/Jakarta')->endOfDay());
        })->when($validated['log_level'] ?? null, function ($query, $log_level) {
            $log_level === 'all' ? $log_level = 'debug' : $log_level;

            return $query->where('level', '>=', Logger::toMonologLevel($log_level));
        })->when($validated['log_message'] ?? null, function ($query, $log_message) {
            return $query->where('message', 'ilike', '%'.$log_message.'%');
        })->when($validated['log_extra'] ?? null, function ($query, $log_extra) {
            return $query->where('context', 'ilike', '%'.$log_extra.'%');
        })->orderBy('id', 'desc')->paginate($validated['per_page'] ?? 20, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json($data);
    }

    public function getRouteAnalytics(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User requesting route analytics', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'date_start' => ['nullable', 'date', 'before_or_equal:date_end'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'route' => ['nullable', 'string'],
            'method' => ['nullable', 'string'],
            'user_name' => ['nullable', 'string'],
            'status_code' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $query = RouteAnalytics::query()
            ->when($validated['date_start'] ?? null, function ($builder, $dateStart) {
                return $builder->where('created_at', '>=', Carbon::parse($dateStart, 'Asia/Jakarta')->startOfDay());
            })
            ->when($validated['date_end'] ?? null, function ($builder, $dateEnd) {
                return $builder->where('created_at', '<=', Carbon::parse($dateEnd, 'Asia/Jakarta')->endOfDay());
            })
            ->when($validated['route'] ?? null, function ($builder, $route) {
                return $builder->where('path', 'like', '%'.$route.'%');
            })
            ->when($validated['method'] ?? null, function ($builder, $method) {
                return $builder->where('method', strtoupper($method));
            })
            ->when($validated['user_name'] ?? null, function ($builder, $userName) {
                return $builder->where('user_name', 'like', '%'.$userName.'%');
            })
            ->when($validated['status_code'] ?? null, function ($builder, $statusCode) {
                return $builder->where('status_code', $statusCode);
            });

        $summaryQuery = clone $query;
        $totalHits = (clone $summaryQuery)->count();
        $uniqueUsers = (clone $summaryQuery)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $topEndpoints = (clone $summaryQuery)
            ->select('method', 'path')
            ->selectRaw('count(*) as hits')
            ->groupBy('method', 'path')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();
        $topUsers = (clone $summaryQuery)
            ->whereNotNull('user_id')
            ->select('user_id', 'user_name')
            ->selectRaw('count(*) as hits')
            ->groupBy('user_id', 'user_name')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        $data = $query
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 20, ['*'], 'page', $validated['page'] ?? 1);

        return response()->json([
            'summary' => [
                'total_hits' => $totalHits,
                'unique_users' => $uniqueUsers,
                'top_endpoints' => $topEndpoints,
                'top_users' => $topUsers,
            ],
            'records' => $data,
        ]);
    }

    /**
     * POST request to clear application cache
     */
    public function postClearAppCache(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User clear app cache', $this->getLogContext($request, $user));

        /** Clear Cache */
        CentralCacheInterfaceClass::flushAllCache();

        return $this->jsonSuccess('Cache cleared', 'Cache cleared successfully');
    }
}
