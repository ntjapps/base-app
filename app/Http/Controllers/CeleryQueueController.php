<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\MessageNotification;
use App\Traits\JsonResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CeleryQueueController extends Controller
{
    use JsonResponse;

    /**
     * POST request to handle send notification to user
     */
    public function sendNotification(Request $request): HttpJsonResponse
    {
        $validate = Validator::make($request->all(), [
            'user_id' => ['required', 'string', 'exists:App\Models\User,id'],
            'message' => ['required', 'string'],
            'lock_id' => ['nullable', 'string'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $user = User::find($validated['user_id']);
        $user->notify(new MessageNotification('Notification', $validated['message']));

        if ($validated['lock_id'] ?? null !== null) {
            Cache::forget($validated['lock_id'].'.rabbitmq.lock');
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
            Cache::forget($validated['task_name'].'.rabbitmq.lock');
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
                Log::error('Unknown callback code', ['code' => $validated['callbacks_code']]);
                break;
        }

        if ($validated['task_name'] ?? null !== null) {
            Cache::forget($validated['task_name'].'.rabbitmq.lock');
        }

        return $this->jsonSuccess('success', 'Callbacks sent successfully');
    }
}
