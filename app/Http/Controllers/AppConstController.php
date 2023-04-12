<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AppConstController extends Controller
{
    /**
     * POST app constants
     */
    public function mainConst(Request $request): HttpJsonResponse
    {
        Log::debug('User '.Auth::guard('api')->user()?->name.' is requesting app constants', ['user_id' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);

        $authCheck = Auth::check() ? true : Auth::guard('api')->check();
        $user = Auth::user() ?? Auth::guard('api')->user();

        /** Menu Items */
        if ($authCheck) {
            if (Gate::forUser($user)->allows('hasSuperPermission', User::class)) {
                $menuItems = json_encode([
                    MenuItemClass::dashboardMenu(),
                    MenuItemClass::editProfileMenu(),
                    MenuItemClass::logoutMenu(),
                    MenuItemClass::administrationMenu(),
                ]);
            } else {
                $menuItems = json_encode([
                    MenuItemClass::dashboardMenu(),
                    MenuItemClass::editProfileMenu(),
                    MenuItemClass::logoutMenu(),
                ]);
            }
        }

        /** Constant now set in Vue State, this now used to check if authenticated or not */
        return response()->json([
            /** App Name */
            'appName' => config('app.name'),
            'userName' => $user?->name ?? $user?->name ?? '',

            /** Check if Auth */
            'isAuth' => $authCheck,

            /** Menu Items */
            'menuItems' => $menuItems ?? json_encode([]),

            /** Permission Data */
            'permissionData' => $user?->getAllPermissions()->pluck('name')->toArray() ?? [],
        ], 200);
    }

    /**
     * POST log when browser unsupported
     */
    public function logAgent(Request $request): HttpJsonResponse
    {
        /** Log unsupported browser trigger from client */
        Log::debug('Unsupported browser trigger', ['user_id' => Auth::guard('api')->id(), 'userAgent' => $request->userAgent(), 'apiUserIp' => $request->ip()]);

        return response()->json('OK', 200);
    }
}
