<?php

namespace App\Http\Controllers;

use App\Interfaces\GoQueues;
use App\Interfaces\MenuItemClass;
use App\Models\Tag;
use App\Traits\GoWorkerFunction;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TagManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET tag management page
     */
    public function tagManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open tag management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Tag Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get tag list from table
     */
    public function getTagList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get tag list for Tag Management', $this->getLogContext($request, $user));

        $tags = Tag::orderBy('name')->get()->toArray();

        return response()->json([
            'data' => $tags,
        ]);
    }

    /**
     * POST request tag man submit
     */
    public function postTagManSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting submit tag for Tag Management', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'id' => ['required_if:type_create,false', 'string', 'exists:App\Models\Tag,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'enabled' => ['required', 'boolean'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('User submit tag for Tag Management validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        // Perform minimal synchronous validation
        if (! $validated['type_create']) {
            $tag = Tag::where('id', $validated['id'])->first();
            if ($tag && $tag->is_system) {
                throw ValidationException::withMessages(['name' => 'System tags cannot be modified']);
            }
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'tag_create_or_update',
            payload: [
                'type_create' => $validated['type_create'],
                'id' => $validated['id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'],
                'enabled' => $validated['enabled'],
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('Tag create/update task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Tag create/update task has been queued',
        ], 202);
    }

    /**
     * POST request delete tag man submit
     */
    public function postDeleteTagManSubmit(Request $request, Tag $tag): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting delete tag for Tag Management', $this->getLogContext($request, $user));

        /** Prevent deleting system tags */
        if ($tag->is_system) {
            Log::warning('Attempt to delete system tag blocked', $this->getLogContext($request, $user, ['tag' => $tag->name]));
            throw ValidationException::withMessages(['tag' => 'System tags cannot be deleted']);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'tag_delete',
            payload: [
                'tag_id' => $tag->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('Tag delete task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'deleted_tag_id' => $tag->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Tag deletion task has been queued',
        ], 202);
    }
}
