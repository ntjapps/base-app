<?php

namespace App\Http\Controllers;

use App\Traits\MenuItemConst;
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
    public function mainConst(): HttpJsonResponse
    {
        Log::debug('User '.Auth::user()?->name.' is requesting app constants', ['user_id' => Auth::id()]);

        /** Menu Items */
        if (Auth::check() ? true : Auth::guard('sanctum')->check()) {
            if (Gate::allows('hasSuperPermission', User::class)) {
                $menuItems = json_encode([
                    MenuItemConst::dashboardMenu(),
                    MenuItemConst::editProfileMenu(),
                    MenuItemConst::logoutMenu(),
                    MenuItemConst::administrationMenu(),
                ]);
            } else {
                $menuItems = json_encode([
                    MenuItemConst::dashboardMenu(),
                    MenuItemConst::editProfileMenu(),
                    MenuItemConst::logoutMenu(),
                ]);
            }
        }

        /** Constant now set in Vue State, this now used to check if authenticated or not */
        return response()->json([
            /** App Name */
            'appName' => config('app.name'),

            /** Check if Auth */
            'isAuth' => Auth::check() ? true : Auth::guard('sanctum')->check(),

            /** Menu Items */
            'menuItems' => $menuItems ?? json_encode([]),
        ], 200);
    }

    /**
     * POST log when browser unsupported
     */
    public function logAgent(Request $request): HttpJsonResponse
    {
        /** Log unsupported browser trigger from client */
        Log::debug('Unsupported browser trigger', ['user_id' => Auth::id(), 'userAgent' => $request->userAgent()]);

        return response()->json('OK', 200);
    }
}
