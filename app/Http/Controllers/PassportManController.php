<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Traits\JsonResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class PassportManController extends Controller
{
    use JsonResponse;

    /**
     * GET passport management page
     */
    public function passportManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open passport management page', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

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
        Log::debug('User list all passport clients', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

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
    public function resetClientSecret(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User reset passport client secret', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:oauth_clients,id'],
            'old_secret' => ['nullable', 'string', 'min:40', 'max:40'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User reset passport client secret', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validatedLog)]);

        $secret = $validated['old_secret'] ?? Str::random(40);
        $client = Passport::client()->where('id', $validated['id'])->first();
        $client->secret = $secret;
        $client->save();

        return $this->jsonSuccess(__('app.passport.reset.title'), __('app.passport.reset.message'), null, ['secret' => $secret]);
    }

    /**
     * POST delete passport client
     */
    public function deletePassportClient(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User delete passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:oauth_clients,id'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User delete passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validatedLog)]);

        $client = Passport::client()->where('id', $validated['id'])->first();
        if (in_array($client->id, [config('passport.personal_access_client.id'), config('passport.client_credentials_grant_client.id')])) {
            throw ValidationException::withMessages(['id' => 'Cannot delete this client']);
        }
        $client->delete();

        return $this->jsonSuccess(__('app.passport.delete.title'), __('app.passport.delete.message'));
    }

    /**
     * POST update passport client
     */
    public function updatePassportClient(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User update passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:oauth_clients,id'],
            'name' => ['required', 'string', 'max:255', 'unique:oauth_clients,name,'.$request->input('id')],
            'redirect' => ['nullable', 'string', 'url'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User update passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validatedLog)]);

        $client = Passport::client()->where('id', $validated['id'])->first();
        if (in_array($client->id, [config('passport.personal_access_client.id'), config('passport.client_credentials_grant_client.id')])) {
            throw ValidationException::withMessages(['id' => 'Cannot update this client']);
        }
        $client->name = $validated['name'];
        $client->redirect_uris = $validated['redirect'] ?? '';
        $client->save();

        return $this->jsonSuccess(__('app.passport.update.title'), __('app.passport.update.message'));
    }

    /**
     * POST create passport client
     */
    public function createPassportClient(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User create passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'ip' => $request->ip()]);

        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:oauth_clients,name'],
            'redirect' => ['nullable', 'string', 'url'],
            'grant_types' => ['required', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User create passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validatedLog)]);

        $client = null;
        $repo = new ClientRepository;
        $grantTypes = $validated['grant_types'];
        $redirects = isset($validated['redirect']) ? [$validated['redirect']] : [];

        if (in_array('personal_access', $grantTypes)) {
            $client = $repo->createPersonalAccessGrantClient($validated['name']);
        } elseif (in_array('client_credentials', $grantTypes)) {
            $client = $repo->createClientCredentialsGrantClient($validated['name']);
        } elseif (in_array('password', $grantTypes)) {
            $client = $repo->createPasswordGrantClient($validated['name']);
        } elseif (in_array('authorization_code', $grantTypes)) {
            $client = $repo->createAuthorizationCodeGrantClient($validated['name'], $redirects);
        } elseif (in_array('implicit', $grantTypes)) {
            $client = $repo->createImplicitGrantClient($validated['name'], $redirects);
        } elseif (in_array('urn:ietf:params:oauth:grant-type:device_code', $grantTypes)) {
            $client = $repo->createDeviceAuthorizationGrantClient($validated['name']);
        } else {
            // fallback: create as client credentials
            $client = $repo->createClientCredentialsGrantClient($validated['name']);
        }
        $secret = $client->secret;

        return $this->jsonSuccess(__('app.passport.create.title'), __('app.passport.create.message'), null, ['id' => $client->id, 'secret' => $secret]);
    }
}
