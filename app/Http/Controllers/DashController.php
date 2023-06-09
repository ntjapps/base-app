<?php

namespace App\Http\Controllers;

use App\Traits\JsonResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashController extends Controller
{
    use JsonResponse;

    /**
     * GET dashboard page
     */
    public function dashboardPage(Request $request): View
    {
        Log::debug('User '.Auth::user()->username.' accessed dashboard page', ['userId' => Auth::id(), 'remoteIp' => $request->ip()]);

        return view('dash-pg.dashboard');
    }

    /**
     * GET edit profile page
     */
    public function profilePage(Request $request): View
    {
        Log::debug('User '.Auth::user()->username.' accessed profile page', ['userId' => Auth::id(), 'remoteIp' => $request->ip()]);

        return view('dash-pg.profile');
    }

    /**
     * POST update profile
     */
    public function updateProfile(Request $request): HttpJsonResponse
    {
        Log::debug('User '.Auth::guard('api')->user()->username.' updating profile', ['userId' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);
        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['prohibited_if:password,null'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        Log::info('User '.Auth::guard('api')->user()->username.' updating profile', ['userId' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);

        $user = Auth::guard('api')->user();
        $user->name = $validated['name'];
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        Log::notice('User '.Auth::guard('api')->user()->username.' updated profile', ['userId' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);

        /** Successful Update Profile */
        (string) $title = 'Update Profile Success';
        (string) $message = 'Profile updated successfully';
        (string) $route = route('dashboard');

        return $this->jsonSuccess($title, $message, $route);
    }
}
