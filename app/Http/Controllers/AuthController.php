<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\AuthFunction;
use App\Traits\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OTPHP\TOTP;

class AuthController extends Controller
{
    use JsonResponse, AuthFunction;

    /**
     * GET request for login landing page
     */
    public function loginPage()
    {
      return view('auth-pg.login');
    }

    /**
     * POST request for logout
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function postLogout(Request $request)
    {
      /** Call common logout function */
      $this->checkAuthLogout($request);

      /** Send user to route */
      (string)$title = 'Logout success';
      (string)$message = 'Thank you';
      (string)$route = route('landing-page');
      return $this->jsonSuccess($title,$message,$route);
    }

    /**
     * POST request for login
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function postLogin(Request $request)
    {
      /** Validate request */
      (array)$validated = Validator::make($request->all(), [
        'username' => 'required|string',
        'password' => 'required|string',
      ])->validated();

      /** Call common logout function */
      $this->checkAuthLogout($request);

      /** Attempt to login */
      $user = User::where('username', $validated['username'])->first();
      /** Check against password */
      (bool)$passCheck = Hash::check($validated['password'], $user?->password) ? true : false;
      /** Check against TOTP */
      (bool)$passCheck = $passCheck || TOTP::create($user?->totp_key)->now() == $validated['password'] ? true : false;

      /** If user not found or password false return failed */
      if ($passCheck === false) {
        Log::warning('Username '.$validated['username'].' failed to login', ['username' => $validated['username']]);
        (string)$title = 'Login failed';
        (string)$message = 'Username or password is incorrect';
        return $this->jsonFailed($title,$message);
      }

      Log::info('Username '.$validated['username'].' logging in', ['username' => $validated['username']]);

      /** Now login with custom auth */
      Auth::login($user);
      $request->session()->regenerate();

      Log::notice('User '.Auth::user()?->name.' logged in', ['user_id' => Auth::id()]);

      /** Send user to dashboard */
      (string)$title = 'Login success';
      (string)$message = 'Welcome back';
      (string)$route = route('dashboard');
      return $this->jsonSuccess($title,$message,$route);
    }

    /**
     * POST request for get API Token
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function postToken(Request $request)
    {
      /** Validate request */
      (array)$validated = Validator::make($request->all(), [
        'username' => 'required|string',
        'password' => 'required|string',
        'device_name' => 'required|string',
      ])->validated();

      /** Check if user is valid */
      $user = User::where('username', $validated['username'])->first();
      /** Check against password */
      (bool)$passCheck = Hash::check($validated['password'], $user?->password) ? true : false;
      /** Check against TOTP */
      (bool)$passCheck = $passCheck || TOTP::create($user?->totp_key)->now() == $validated['password'] ? true : false;

      /** If user not found or password false return failed */
      if ($passCheck === false) {
        Log::warning('Username '.$validated['username'].' failed to login', ['username' => $validated['username']]);
        (string)$title = 'Login failed';
        (string)$message = 'Username or password is incorrect';
        return $this->jsonFailed($title,$message);
      }

      Log::info('Username '.$validated['username'].' getting token', ['username' => $validated['username']]);

      /** Generate user API Token */
      $tokenGen = $user->createToken($validated['device_name']);
      (string)$token = $tokenGen->plainTextToken;
      (string)$expire = Carbon::now()->addMinutes(config('sanctum.expiration'))->toDateTimeString();

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
}
