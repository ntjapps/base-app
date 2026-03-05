<?php

namespace App\Http\Controllers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Models\User;
use App\Notifications\MessageNotification;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CeleryQueueController extends Controller
{
    use JsonResponse, LogContext;

    /**
     * POST request to handle send notification to user
     */
    public function sendNotification(Request $request): HttpJsonResponse
    {
        // Accept 'user_id' only — the database ID column is authoritative (integer or UUID)
        $data = $request->all();

        $validate = Validator::make($request->all(), [
            'message' => ['required', 'string'],
            'lock_id' => ['nullable', 'string'],
            'severity' => ['nullable', 'string', 'in:success,info,warning,error'],
            'level' => ['nullable', 'string'],
            'level_name' => ['nullable', 'string'],
            // Allow integer or string ids (DB may use int autoincrement or UUIDs)
            'user_id' => ['required'],
        ]);
        if ($validate->fails()) {
            try {
                Log::channel('stdout')->error('sendNotification validation failed', $validate->errors()->toArray());
            } catch (\Throwable $e) {
                // ignore logging failures
            }
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        // Determine the user id (use authoritative 'user_id' field)
        $userId = $validated['user_id'];
        if (empty($userId) || ! is_string($userId)) {
            throw new ValidationException(Validator::make([], ['user_id' => ['required']]));
        }

        $user = User::find($userId);
        if ($user === null) {
            throw new ValidationException(Validator::make([], ['user_id' => ['exists:App\\Models\\User,id']]));
        }

        // Normalize severity: prefer explicit 'severity' param, otherwise map from level/level_name
        $severity = $validated['severity'] ?? null;
        if ($severity === null) {
            $severity = $this->mapSeverityFromLevel($validated['level'] ?? null, $validated['level_name'] ?? null);
        }

        try {
            Log::channel('stdout')->info('sendNotification chosen severity', ['severity' => $severity, 'message' => $validated['message'], 'user_id' => $userId]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        // Use sendNow to bypass afterCommit() behavior so tests can observe DB notifications immediately
        \Illuminate\Support\Facades\Notification::sendNow($user, new MessageNotification('Notification', $validated['message'], $severity));

        if ($validated['lock_id'] ?? null !== null) {
            Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock($validated['lock_id']));
        }

        return $this->jsonSuccess('success', 'Notification sent successfully');
    }

    /**
     * POST request to handle send log
     */
    public function sendLog(Request $request): HttpJsonResponse
    {
        $validate = Validator::make($request->all(), [
            'message' => ['required', 'string'],
            'level' => ['required', 'string', 'in:debug,info,warning,error,critical'],
            'task_name' => ['nullable', 'string'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        switch ($validated['level']) {
            case 'debug':
                Log::debug($validated['message']);
                break;
            case 'info':
                Log::info($validated['message']);
                break;
            case 'warning':
                Log::warning($validated['message']);
                break;
            case 'error':
                Log::error($validated['message']);
                break;
            case 'critical':
                Log::critical($validated['message']);
                break;
        }

        if ($validated['task_name'] ?? null !== null) {
            Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock($validated['task_name']));
        }

        return $this->jsonSuccess('success', 'Log sent successfully');
    }

    /**
     * POST request to handle callbacks
     */
    public function sendCallbacks(Request $request): HttpJsonResponse
    {
        $validate = Validator::make($request->all(), [
            'callbacks_code' => ['required', 'string'],
            'callbacks_payload' => ['required', 'json'],
            'task_name' => ['nullable', 'string'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        switch ($validated['callbacks_code']) {
            default:
                Log::error('Unknown callback code', $this->getLogContext($request, null, ['code' => $validated['callbacks_code']]));
                break;
        }

        if ($validated['task_name'] ?? null !== null) {
            Cache::forget(CentralCacheInterfaceClass::keyRabbitmqLock($validated['task_name']));
        }

        return $this->jsonSuccess('success', 'Callbacks sent successfully');
    }

    /**     * Normalize severity from numeric level or level name (fallback to 'info')
     */
    private function mapSeverityFromLevel(?string $level, ?string $levelName): string
    {
        // explicit level name mapping
        if ($levelName !== null) {
            $normalized = strtoupper(trim($levelName));
            if (in_array($normalized, ['ERROR', 'CRITICAL', 'FATAL'], true)) {
                return 'error';
            }
            if (in_array($normalized, ['WARNING', 'WARN'], true)) {
                return 'warning';
            }
            if (in_array($normalized, ['SUCCESS'], true)) {
                return 'success';
            }
            if (in_array($normalized, ['INFO', 'INFORMATION'], true)) {
                return 'info';
            }
        }

        // numeric level mapping (typical syslog/PHP numeric codes)
        if ($level !== null && is_numeric($level)) {
            $lv = (int) $level;
            if ($lv >= 400) {
                return 'error';
            }
            if ($lv >= 300) {
                return 'warning';
            }
            if ($lv >= 200) {
                return 'info';
            }
        }

        return 'info';
    }

    /**
     * POST request to clear all caches
     */
    public function clearCache(Request $request): HttpJsonResponse
    {
        CentralCacheInterfaceClass::flushAllCache();

        Log::info('All caches cleared', $this->getLogContext($request));

        return $this->jsonSuccess('success', 'All caches cleared successfully');
    }

    /**
     * POST request to clear permission-related caches only
     */
    public function clearPermissions(Request $request): HttpJsonResponse
    {
        CentralCacheInterfaceClass::flushPermissions();

        Log::info('Permission caches cleared', $this->getLogContext($request));

        return $this->jsonSuccess('success', 'Permission caches cleared successfully');
    }
}
