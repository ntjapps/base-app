<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TaskStatusController extends Controller
{
    use JsonResponse, LogContext;

    /**
     * Get task status by ID
     */
    public function getTaskStatus(Request $request, string $taskId): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User requesting task status', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        $task = TaskStatus::find($taskId);

        if (! $task) {
            return response()->json([
                'status' => 'error',
                'message' => 'Task not found',
            ], 404);
        }

        // Optional: Check if user has permission to view this task
        if ($task->user_id && $user && $task->user_id !== $user->id) {
            // Allow super users to see all tasks
            if (! $user->can('hasSuperPermission')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this task',
                ], 403);
            }
        }

        return response()->json([
            'id' => $task->id,
            'task_name' => $task->task_name,
            'status' => $task->status,
            'queue' => $task->queue,
            'attempt' => $task->attempt,
            'max_attempts' => $task->max_attempts,
            'queued_at' => $task->queued_at?->toIso8601String(),
            'started_at' => $task->started_at?->toIso8601String(),
            'completed_at' => $task->completed_at?->toIso8601String(),
            'failed_at' => $task->failed_at?->toIso8601String(),
            'result' => $task->result,
            'error_message' => $task->error_message,
            'is_terminal' => $task->isTerminal(),
            'is_pending' => $task->isPending(),
        ]);
    }

    public function getTaskList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User requesting task list', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'status' => ['nullable', 'string', 'max:50'],
            'task_name' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::debug('Task list request validation passed', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $query = TaskStatus::query();

        // Filter by user unless super admin
        if (! $user || ! $user->can('hasSuperPermission')) {
            $query->where('user_id', $user?->id);
        }

        // Optional filters
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['task_name'])) {
            $query->where('task_name', $validated['task_name']);
        }

        $tasks = $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'task_name' => $task->task_name,
                    'status' => $task->status,
                    'queue' => $task->queue,
                    'queued_at' => $task->queued_at?->toIso8601String(),
                    'completed_at' => $task->completed_at?->toIso8601String(),
                    'failed_at' => $task->failed_at?->toIso8601String(),
                    'is_terminal' => $task->isTerminal(),
                ];
            });

        return response()->json($tasks);
    }
}
