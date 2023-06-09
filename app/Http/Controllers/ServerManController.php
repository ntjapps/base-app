<?php

namespace App\Http\Controllers;

use App\Logger\Models\ServerLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Monolog\Logger;

class ServerManController extends Controller
{
    /**
     * GET request to view server logs layouts
     */
    public function serverLogs(Request $request): View
    {
        Log::debug('User '.Auth::user()->name.' open server log', ['userId' => Auth::id(), 'remoteIp' => $request->ip()]);

        return view('super-pg.serverlog');
    }

    /**
     * POST request to get server Logs from tables
     */
    public function getServerLogs(Request $request): HttpJsonResponse
    {
        Log::debug('User '.Auth::guard('api')->user()->name.' get server log', ['userId' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date',
            'log_level' => 'nullable|string',
            'log_message' => 'nullable|string',
            'log_extra' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $data = ServerLog::when($validated['date_start'] ?? null, function ($query, $date_start) {
            return $query->where('created_at', '>=', Carbon::parse($date_start, 'Asia/Jakarta')->startOfDay());
        })->when($validated['date_end'] ?? null, function ($query, $date_end) {
            return $query->where('created_at', '<=', Carbon::parse($date_end, 'Asia/Jakarta')->startOfDay()->addDay());
        })->when($validated['log_level'] ?? null, function ($query, $log_level) {
            $log_level === 'all' ? $log_level = 'debug' : $log_level = $log_level;

            return $query->where('level', '>=', Logger::toMonologLevel($log_level));
        })->when($validated['log_message'] ?? null, function ($query, $log_message) {
            return $query->where('message', 'ilike', '%'.$log_message.'%');
        })->when($validated['log_extra'] ?? null, function ($query, $log_extra) {
            return $query->where('context', 'ilike', '%'.$log_extra.'%');
        })->orderBy('id', 'desc')->limit(20000)->get();

        return response()->json($data);
    }
}
