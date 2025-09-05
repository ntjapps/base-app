<?php

namespace App\Http\Controllers;

use App\Interfaces\InterfaceClass;
use App\Interfaces\MenuItemClass;
use App\Logger\Models\ServerLog;
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

    /**
     * POST request to get server Logs from tables
     */
    public function getServerLogs(Request $request): HttpJsonResponse
    {
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

    /**
     * POST request to clear application cache
     */
    public function postClearAppCache(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User clear app cache', $this->getLogContext($request, $user));

        /** Clear Cache */
        Cache::flush();
        InterfaceClass::flushRolePermissionCache();

        return $this->jsonSuccess('Cache cleared', 'Cache cleared successfully');
    }
}
