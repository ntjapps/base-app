<?php

namespace App\Http\Controllers;

use App\Interfaces\InterfaceClass;
use App\Interfaces\MenuItemClass;
use App\Models\User;
use App\Rules\TokenPlatformValidation;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;

class AppConstController extends Controller
{
    /**
     * POST app constants
     */
    public function mainConst(Request $request): HttpJsonResponse
    {

        try {
            $authCheck = Auth::check() ? true : Auth::guard('api')->check();
            $user = Auth::user() ?? Auth::guard('api')->user();
            Log::debug('User is requesting app constants', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);
        } catch (OAuthServerException $e) {
            Log::warning('Client is requesting app constants but not authenticated', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

            return response()->json([
                'isAuth' => false,
            ], 200);
        }

        /** Menu Items */
        if ($authCheck) {
            if (Gate::forUser($user)->allows('hasSuperPermission', User::class)) {
                $menuArray = [
                    MenuItemClass::dashboardMenu(),
                    MenuItemClass::editProfileMenu(),
                    MenuItemClass::logoutMenu(),
                    MenuItemClass::administrationMenu(),
                ];
            } else {
                $menuArray = [
                    MenuItemClass::dashboardMenu(),
                    MenuItemClass::editProfileMenu(),
                    MenuItemClass::logoutMenu(),
                ];
            }

            $menuItems = json_encode(array_filter($menuArray));
        }

        /** Constant now set in Vue State, this now used to check if authenticated or not */
        /** @disregard P1013 Auth facade used to fetch model */
        return response()->json([
            /** App Name */
            'appName' => config('app.name'),
            'appVersion' => InterfaceClass::readApplicationVersion(),
            'userName' => $user?->name ?? $user?->name ?? '',

            /** Check if Auth */
            'isAuth' => $authCheck,

            /** Menu Items */
            'menuItems' => $menuItems ?? json_encode([]),

            /** Permission Data */
            'permissionData' => $user?->getAllPermissions()->pluck('name')->toArray() ?? [],
            'directPermissionData' => $user?->getDirectPermissions()->pluck('name')->toArray() ?? [],
            'directRoleData' => $user?->getRoleNames()->toArray() ?? [],
        ], 200);
    }

    /**
     * POST log when browser unsupported
     */
    public function logAgent(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        /** Log unsupported browser trigger from client */
        Log::debug('Unsupported browser trigger', ['userId' => $user?->id, 'userName' => $user?->name, 'userAgent' => $request->userAgent(), 'apiUserIp' => $request->ip()]);

        return response()->json('OK', 200);
    }

    /**
     * POST app version updater
     */
    public function getCurrentAppVersion(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('API hit trigger get current app version', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'app_version' => ['required', 'string'],
            'device_id' => ['required', 'string'],
            'device_platform' => ['required', new TokenPlatformValidation],
        ]);
        if ($validate->fails()) {
            Log::warning('API hit trigger validation failed', ['apiUserIp' => $request->ip(), 'errors' => $validate->errors()]);

            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('API hit trigger validation', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip(), 'validated' => $validatedLog]);

        /** Get Current App Version */
        (string) $currentAppVersion = config('mobile.app_version');
        (bool) $forceUpdate = config('mobile.app_force_update');

        /** If force update then submit force update */
        if ($forceUpdate) {
            Log::info('API hit trigger force update', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

            return response()->json([
                'appUpdate' => true,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        }

        /** Check Current Semversion from Application */
        if (substr($validated['app_version'], 0, 1) === 'v') {
            (bool) $checkSemVersion = version_compare($validated['app_version'], $currentAppVersion, '>=');
        } else {
            (bool) $checkSemVersion = $validated['app_version'] === $currentAppVersion;
        }

        /** If current version is same with device version then submit no update */
        if ($checkSemVersion) {
            Log::info('API hit trigger no update', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

            return response()->json([
                'appUpdate' => false,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        } else {
            Log::info('API hit trigger update', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

            return response()->json([
                'appUpdate' => true,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        }
    }
}
