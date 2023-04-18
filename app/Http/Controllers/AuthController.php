<?php

namespace App\Http\Controllers;

use App\Interfaces\InterfaceClass;
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
    use JsonResponse, AuthFunction;

    /**
     * GET request for login landing page
     */
    public function loginPage(Request $request): View
    {
        Log::debug('Computer access login page', ['remoteIp' => $request->ip()]);

        return view('auth-pg.login');
    }

    /**
     * POST request for logout
     */
    public function postLogout(Request $request): HttpJsonResponse
    {
        Log::debug('User '.Auth::user()->name.' logging out', ['user_id' => Auth::id(), 'remoteIp' => $request->ip()]);

        /** Call common logout function */
        $this->checkAuthLogout($request);

        /** Send user to route */
        (string) $title = 'Logout success';
        (string) $message = 'Thank you';
        (string) $route = route('landing-page');

        return $this->jsonSuccess($title, $message, $route);
    }

    /**
     * GET request for logout
     */
    public function getLogout(Request $request): RedirectResponse
    {
        Log::debug('Computer Access Logout Request', ['remoteIp' => $request->ip()]);

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
        Log::debug('Computer access post login', ['remoteIp' => $request->ip()]);

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

        /** If user not found or password false return failed */
        if (is_null($user = $this->checkAuthUser($validated))) {
            Log::warning('Username '.$validated['username'].' failed to login', ['username' => $validated['username']]);
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect',
            ]);
        }

        Log::info('Username '.$validated['username'].' logging in', ['username' => $validated['username']]);

        /** Now login with custom auth */
        Auth::login($user);
        $request->session()->regenerate();

        Log::notice('User '.Auth::user()->name.' logged in', ['user_id' => Auth::id()]);

        /** Send user to dashboard */
        (string) $title = 'Login success';
        (string) $message = 'Welcome back';
        (string) $route = route('dashboard');

        return $this->jsonSuccess($title, $message, $route);
    }

    /**
     * POST request for get API Token
     */
    public function postToken(Request $request): HttpJsonResponse
    {
        Log::debug('Computer access post token', ['apiUserIp' => $request->ip()]);

        /** Validate request */
        $validate = Validator::make($request->all(), [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_id' => ['required', 'uuid'],
            'device_name' => ['required', 'string'],
            'device_model' => ['required', 'string'],
            'device_platform' => ['required', new TokenPlatformValidation],
            'token' => ['required', 'string', new TurnstileValidation],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        /** If user not found or password false return failed */
        if (is_null($user = $this->checkAuthUser($validated))) {
            Log::warning('Username '.$validated['username'].' failed to login', ['username' => $validated['username']]);
            throw ValidationException::withMessages([
                'username' => 'Username or password is incorrect',
            ]);
        }

        Log::info('Username '.$validated['username'].' getting token', ['username' => $validated['username']]);

        /** Generate user API Token */
        (string) $token = $user->createToken($validated['device_name'])->accessToken;
        (string) $expire = InterfaceClass::getPassportTokenLifetime()->toDateTimeString();

        Log::notice('Username '.$validated['username'].' got token', ['username' => $validated['username']]);

        return response()->json([
            'status' => 'success',
            'title' => 'Token generated',
            'message' => 'Token generated',
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
        Log::info('Username '.Auth::guard('api')->user()->username.' revoking token', ['username' => Auth::guard('api')->user()->username, 'apiUserIp' => $request->ip()]);

        /** Match bearer token with access token */
        $request->user()->token()->revoke();

        Log::notice('Username '.Auth::guard('api')->user()->username.' revoked token', ['username' => Auth::guard('api')->user()->username, 'apiUserIp' => $request->ip()]);

        return response()->json([
            'status' => 'success',
            'title' => 'Token revoked',
            'message' => 'Token revoked',
        ], 200);
    }
}
