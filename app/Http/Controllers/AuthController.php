<?php

namespace App\Http\Controllers;

use App\Interfaces\InterfaceClass;
use App\Interfaces\MenuItemClass;
use App\Models\User;
use App\Rules\TokenPlatformValidation;
use App\Rules\TurnstileValidation;
use App\Traits\AuthFunction;
use App\Traits\JsonResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    use AuthFunction, JsonResponse;

    /**
     * GET request for login landing page
     */
    public function loginPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('Computer access login page', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        return view('base-components.base-vue', [
            'pageTitle' => 'Login',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request for logout
     */
    public function postLogout(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User logging out', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Call common logout function */
        $this->checkAuthLogout($request);

        /** Send user to route */
        (string) $title = __('app.logout.title');
        (string) $message = __('app.logout.message');
        (string) $route = route('landing-page');

        return $this->jsonSuccess($title, $message, $route);
    }

    /**
     * GET request for logout
     */
    public function getLogout(Request $request): RedirectResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('Computer Access Logout Request', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        /** Call common logout function */
        $this->checkAuthLogout($request);

        /** Send user to route */
        return redirect()->route('landing-page');
    }

    /**
     * POST request for login
     */
    public function postLogin(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('Computer access post login', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate request */
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'token' => ['required', 'string', new TurnstileValidation],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        unset($validatedLog['password']);
        unset($validatedLog['token']);
        Log::info('Username logging in validation', ['username' => $validated['username'], 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        /** If user not found or password false return failed */
        if (is_null($user = $this->checkAuthUser($validated))) {
            Log::warning('Username failed to login', ['username' => $validated['username'], 'apiUserIp' => $request->ip()]);
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect',
            ]);
        }

        Log::info('Username logging in', ['username' => $validated['username'], 'apiUserIp' => $request->ip()]);

        /** Now login with custom auth */
        Auth::login($user);
        $request->session()->regenerate();

        Log::notice('User logged in', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Send user to dashboard */
        (string) $title = __('app.login.title');
        (string) $message = __('app.login.message');
        (string) $route = route('dashboard');

        return $this->jsonSuccess($title, $message, $route);
    }

    /**
     * POST request for get API Token
     */
    public function postToken(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('Computer access post token', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate request */
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'string'],
            'device_name' => ['required', 'string'],
            'device_model' => ['required', 'string'],
            'device_platform' => ['required', new TokenPlatformValidation],
            'token' => ['required', 'string', new TurnstileValidation],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        unset($validatedLog['password']);
        Log::info('Username getting token validation', ['username' => $validated['username'], 'apiUserIp' => $request->ip(), 'validated' => json_encode($validatedLog)]);

        /** If user not found or password false return failed */
        if (is_null($user = $this->checkAuthUser($validated))) {
            Log::warning('Username failed to login', ['username' => $validated['username'], 'apiUserIp' => $request->ip()]);
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect',
            ]);
        }

        Log::info('Username getting token', ['username' => $validated['username'], 'apiUserIp' => $request->ip()]);

        /** Generate user API Token */
        (string) $token = $user->createToken($validated['device_name'])->accessToken;
        (string) $expire = InterfaceClass::getPassportTokenLifetime()->toDateTimeString();

        Log::notice('Username got token', ['username' => $validated['username'], 'apiUserIp' => $request->ip()]);

        return response()->json([
            'status' => 'success',
            'title' => __('app.token.generated.title'),
            'message' => __('app.token.generated.message'),
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_at' => $expire,
        ], 200);
    }

    /**
     * POST request for revoke API Token
     */
    public function postTokenRevoke(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('Username revoking token', ['userId' => $user?->id, 'userName' => $user?->name, 'username' => $user?->username, 'apiUserIp' => $request->ip()]);

        /** Match bearer token with access token */
        $request->user()->token()->revoke();

        Log::notice('Username revoked token', ['userId' => $user?->id, 'userName' => $user?->name, 'username' => $user?->username, 'apiUserIp' => $request->ip()]);

        return response()->json([
            'status' => 'success',
            'title' => __('app.token.revoked.title'),
            'message' => __('app.token.revoked.message'),
        ], 200);
    }
}
