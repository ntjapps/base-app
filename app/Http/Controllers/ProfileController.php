<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\JsonResponse;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use JsonResponse;

    /**
     * GET edit profile page
     */
    public function profilePage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessed profile page', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        return view('dash-pg.profile');
    }

    /**
     * POST update profile
     */
    public function updateProfile(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User updating profile', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['prohibited_if:password,null'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        Log::info('User updating profile', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        $user->name = $validated['name'];
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        /** @disregard P1013 Auth facade fetch user model */
        $user->save();

        Log::notice('User updated profile', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Successful Update Profile */
        (string) $title = __('app.profile.update.title');
        (string) $message = __('app.profile.update.message');
        (string) $route = route('dashboard');

        return $this->jsonSuccess($title, $message, $route);
    }
}
