<?php

namespace App\Http\Controllers;

use App\Interfaces\GoQueues;
use App\Interfaces\MenuItemClass;
use App\Traits\GoWorkerFunction;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;

class PassportManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET passport management page
     */
    public function passportManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open passport management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => __('app.passport.title'),
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST list all clients
     */
    public function listPassportClients(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User list all passport clients', $this->getLogContext($request, $user));

        $clients = Passport::client()->orderBy('name', 'asc')->get()->map(function (Client $client) {
            return collect($client)->merge([
                'is_personal_access' => in_array('personal_access', $client->grant_types ?? []),
                'is_client_credentials' => in_array('client_credentials', $client->grant_types ?? []),
                'allowed_action' => ! in_array($client->id, [config('passport.personal_access_client.id'), config('passport.client_credentials_grant_client.id'), config('passport.client_credentials_rabbitmq_client.id')]),
            ]);
        });

        return response()->json($clients);
    }

    /**
     * POST reset client secret
     */
    public function resetClientSecret(Request $request, Client $client): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User reset passport client secret', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'old_secret' => ['nullable', 'string', 'min:40', 'max:40'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        // Do not log secrets - mask old_secret if present
        if (isset($validatedLog['old_secret'])) {
            $validatedLog['old_secret'] = '***';
        }
        Log::notice('User reset passport client secret', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $secret = $validated['old_secret'] ?? Str::random(40);

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'oauth_client_reset_secret',
            payload: [
                'client_id' => $client->id,
                'secret' => $secret,
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('OAuth client secret reset task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'client_id' => $client->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Client secret reset task has been queued',
            'secret' => $secret,
        ], 202);
    }

    /**
     * POST delete passport client
     */
    public function deletePassportClient(Request $request, Client $client): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User delete passport client', $this->getLogContext($request, $user));

        if (in_array($client->id, [config('passport.personal_access_client.id'), config('passport.client_credentials_grant_client.id'), config('passport.client_credentials_rabbitmq_client.id')])) {
            throw ValidationException::withMessages(['id' => 'Cannot delete this client']);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'oauth_client_delete',
            payload: [
                'client_id' => $client->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('OAuth client delete task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'deleted_client_id' => $client->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Client deletion task has been queued',
        ], 202);
    }

    /**
     * POST update passport client
     */
    public function updatePassportClient(Request $request, Client $client): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User update passport client', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'exists:oauth_clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['nullable', 'string', 'url'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        // Secondary validation for unique name ignoring current client
        $uniqueCheck = Validator::make($validated, [
            'name' => [Rule::unique('oauth_clients', 'name')->ignore($validated['id'])],
        ]);
        if ($uniqueCheck->fails()) {
            throw new ValidationException($uniqueCheck);
        }

        $validatedLog = $validated;
        Log::notice('User update passport client validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        if (in_array($client->id, [config('passport.personal_access_client.id'), config('passport.client_credentials_grant_client.id')])) {
            throw ValidationException::withMessages(['id' => 'Cannot update this client']);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'oauth_client_update',
            payload: [
                'client_id' => $client->id,
                'name' => $validated['name'],
                'redirect_uris' => isset($validated['redirect']) ? [$validated['redirect']] : [],
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('OAuth client update task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'client_id' => $client->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Client update task has been queued',
        ], 202);
    }

    /**
     * POST create passport client
     */
    public function createPassportClient(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User create passport client', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:oauth_clients,name'],
            'redirect' => ['nullable', 'string', 'url'],
            'grant_types' => ['nullable', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User create passport client validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $grantTypes = $validated['grant_types'] ?? [];
        $redirects = isset($validated['redirect']) ? [$validated['redirect']] : ['http://localhost'];

        // Generate secret for the client
        $secret = Str::random(40);

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'oauth_client_create',
            payload: [
                'name' => $validated['name'],
                'redirect_uris' => $redirects,
                'grant_types' => $grantTypes,
                'secret' => $secret,
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('OAuth client create task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Client creation task has been queued',
            'secret' => $secret,
        ], 202);
    }
}
