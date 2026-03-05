<?php

namespace App\Http\Controllers;

use App\Interfaces\GoQueues;
use App\Interfaces\MenuItemClass;
use App\Models\AiModelInstruction;
use App\Traits\GoWorkerFunction;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiModelInstructionManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET AI model instruction management page
     */
    public function aiModelInstructionManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open AI model instruction management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'AI Model Instruction Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get AI model instruction list from table
     */
    public function getAiModelInstructionList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get AI model instruction list for AI Model Instruction Management', $this->getLogContext($request, $user));

        $instructions = AiModelInstruction::orderBy('name')->get()->toArray();

        return response()->json([
            'data' => $instructions,
        ]);
    }

    /**
     * POST request AI model instruction man submit
     */
    public function postAiModelInstructionManSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested submit AI model instruction', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'id' => ['required_if:type_create,false', 'string', 'exists:App\Models\AiModelInstruction,id'],
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255'],
            'instructions' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
            'scope' => ['nullable', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('AI model instruction submission validation passed', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        // Perform minimal synchronous validation for duplicates
        if ($validated['type_create']) {
            $checkKey = AiModelInstruction::where('key', $validated['key'])->exists();
            if ($checkKey) {
                throw ValidationException::withMessages(['key' => 'Instruction key already exists']);
            }
        } else {
            $checkKey = AiModelInstruction::where('key', $validated['key'])->where('id', '!=', $validated['id'])->exists();
            if ($checkKey) {
                throw ValidationException::withMessages(['key' => 'Instruction key already exists']);
            }
        }

        $payload = [
            'type_create' => $validated['type_create'],
            'id' => $validated['id'] ?? null,
            'name' => $validated['name'],
            'key' => $validated['key'],
            'instructions' => $validated['instructions'],
            'enabled' => $validated['enabled'],
            'scope' => $validated['scope'] ?? null,
            'requested_by' => $user?->id ?? null,
        ];

        $taskId = $this->sendGoTask(
            task: 'instruction_create_or_update',
            payload: $payload,
            queue: GoQueues::ADMIN
        );

        Log::notice('Enqueued AI model instruction create/update task', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'AI model instruction create/update task has been queued',
        ], 202);
    }

    /**
     * POST request delete AI model instruction man submit
     */
    public function postDeleteAiModelInstructionManSubmit(Request $request, AiModelInstruction $aiModelInstruction): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested delete AI model instruction', $this->getLogContext($request, $user));

        $payload = [
            'instruction_id' => $aiModelInstruction->id,
            'requested_by' => $user?->id ?? null,
        ];

        $taskId = $this->sendGoTask(
            task: 'instruction_delete',
            payload: $payload,
            queue: GoQueues::ADMIN
        );

        Log::notice('Enqueued AI model instruction delete task', $this->getLogContext($request, $user, ['task_id' => $taskId, 'instruction_id' => $aiModelInstruction->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'AI model instruction delete task has been queued',
        ], 202);
    }

    /**
     * POST import AI model instruction from bundled file into DB
     */
    public function postImportAiModelInstructionFromFile(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested import AI model instruction from file', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'key' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('AI model instruction import validation passed', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $key = $validated['key'] ?? 'support_default';
        $name = $validated['name'] ?? 'Imported from file';

        $path = storage_path('model_instruction.txt');
        if (! file_exists($path)) {
            return response()->json(['status' => 'error', 'message' => 'Model instruction file not found'], 404);
        }
        // enqueue import task and return 202 Accepted with task id
        $payload = [
            'key' => $key,
            'name' => $name,
            'file_path' => $path,
            'requested_by' => $user->id ?? null,
        ];

        $taskId = $this->sendGoTask(
            task: 'instruction_import',
            payload: $payload,
            queue: GoQueues::ADMIN
        );

        Log::notice('Enqueued AI model instruction import task', $this->getLogContext($request, $user, ['task_id' => $taskId, 'key' => $key]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Import task has been queued',
        ], 202);
    }

    /**
     * POST export AI model instruction(s) from DB into bundled file
     */
    public function postExportAiModelInstructionToFile(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User requested export AI model instruction to file', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'id' => ['nullable', 'string', 'exists:App\Models\AiModelInstruction,id'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('AI model instruction export validation passed', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $instructionId = $validated['id'] ?? null;

        $payload = [
            'instruction_id' => $instructionId,
            'requested_by' => $user->id ?? null,
        ];

        $taskId = $this->sendGoTask(
            task: 'instruction_export',
            payload: $payload,
            queue: GoQueues::ADMIN
        );

        Log::notice('Enqueued AI model instruction export task', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Export task has been queued',
        ], 202);
    }
}
