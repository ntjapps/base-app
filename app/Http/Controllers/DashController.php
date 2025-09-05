<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashController extends Controller
{
    use JsonResponse, LogContext;

    /**
     * GET dashboard page
     */
    public function dashboardPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessed dashboard page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Dashboard',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }
}
