<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Models\User;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
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
    use JsonResponse, LogContext;

    /**
     * GET edit profile page
     */
    public function profilePage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessed profile page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Profile',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST update profile
     */
    public function updateProfile(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User updating profile', $this->getLogContext($request, $user));

        $validate = Validator::make($request->all(), [
            'name' => ['required', 'string'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['prohibited_if:password,null'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        unset($validatedLog['password']);
        unset($validatedLog['password_confirmation']);
        Log::info('User updating profile validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        $user->name = $validated['name'];
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        /** @disregard P1013 Auth facade fetch user model */
        $user->save();

        Log::notice('User updated profile', $this->getLogContext($request, $user));

        /** Successful Update Profile */
        (string) $title = __('app.profile.update.title');
        (string) $message = __('app.profile.update.message');
        (string) $route = route('dashboard');

        return $this->jsonSuccess($title, $message, $route);
    }
}
