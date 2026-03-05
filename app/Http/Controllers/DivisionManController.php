<?php

namespace App\Http\Controllers;

use App\Interfaces\GoQueues;
use App\Interfaces\MenuItemClass;
use App\Models\Division;
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

class DivisionManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET division management page
     */
    public function divisionManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open division management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Division Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get division list from table
     */
    public function getDivisionList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get division list for Division Management', $this->getLogContext($request, $user));

        $divisions = Division::orderBy('name')->get()->toArray();

        return response()->json([
            'data' => $divisions,
        ]);
    }

    /**
     * POST request division man submit
     */
    public function postDivisionManSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting submit division for Division Management', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'id' => ['required_if:type_create,false', 'string', 'exists:App\Models\Division,id'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('User submit division for Division Management validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'division_create_or_update',
            payload: [
                'type_create' => $validated['type_create'],
                'id' => $validated['id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'enabled' => $validated['enabled'],
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('Division create/update task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Division create/update task has been queued',
        ], 202);
    }

    /**
     * POST request delete division man submit
     */
    public function postDeleteDivisionManSubmit(Request $request, Division $division): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting delete division for Division Management', $this->getLogContext($request, $user));

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'division_delete',
            payload: [
                'division_id' => $division->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('Division delete task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'deleted_division_id' => $division->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Division deletion task has been queued',
        ], 202);
    }
}
