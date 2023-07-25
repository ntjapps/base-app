<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

            $menuItems = json_encode($menuArray);
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
}
