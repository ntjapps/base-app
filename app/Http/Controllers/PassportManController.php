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
        Log::debug('User open passport management page', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        return view('base-components.base-vue', [
            'pageTitle' => 'Passport Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST list all clients
     */
    public function listPassportClients(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User list all passport clients', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        $client = Passport::client()->orderBy('name', 'asc')->get()->map(function (Client $client) {
            return collect($client)->merge([
                'allowed_action' => $client->id !== config('passport.personal_access_client.id') && $client->id !== config('passport.client_credentials_grant_client.id') ? true : false,
            ]);
        });

        return response()->json($client);
    }

    /**
     * POST reset client secret
     */
    public function resetClientSecret(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User reset passport client secret', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:App\Models\PassportClient,id'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User reset passport client secret', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        /** Generate new secret */
        $secret = Str::random(40);

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
        Log::debug('User delete passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:App\Models\PassportClient,id'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User delete passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        $client = Passport::client()->where('id', $validated['id'])->first();

        if ($client->id === config('passport.personal_access_client_name.id') || $client->id === config('passport.client_credentials_grant_client.id')) {
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
        Log::debug('User update passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'uuid', 'exists:App\Models\PassportClient,id'],
            'name' => ['required', 'string', 'max:255', 'unique:App\Models\PassportClient,name,'.$request->input('id')],
            'redirect' => ['nullable', 'string', 'url'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User update passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        $client = Passport::client()->where('id', $validated['id'])->first();

        if ($client->id === config('passport.personal_access_client_name.id') || $client->id === config('passport.client_credentials_grant_client.id')) {
            throw ValidationException::withMessages(['id' => 'Cannot update this client']);
        }

        $client->name = $validated['name'];
        $client->redirect = $validated['redirect'] ?? '';
        $client->save();

        return $this->jsonSuccess(__('app.passport.update.title'), __('app.passport.update.message'));
    }

    /**
     * POST create passport client
     */
    public function createPassportClient(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User create passport client', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', 'unique:App\Models\PassportClient,name'],
            'redirect' => ['nullable', 'string', 'url'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::notice('User create passport client validation', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        $client = (new ClientRepository())->create(null, $validated['name'], $validated['redirect'] ?? '');
        $secret = Str::random(40);

        $client->forceFill([
            'secret' => $secret,
        ])->save();

        return $this->jsonSuccess(__('app.passport.create.title'), __('app.passport.create.message'), null, ['id' => $client->id, 'secret' => $secret]);
    }
}
