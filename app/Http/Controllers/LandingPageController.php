<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    use LogContext;

    /**
     * GET request for landing page
     */
    public function landingPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessing landing page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Custom Software Development',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * GET request for privacy policy page
     */
    public function privacyPolicy(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessing privacy policy page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Privacy Policy',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * GET request for privacy policy page
     */
    public function privacyPolicyWaagent(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessing privacy policy page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Privacy Policy (App)',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * GET request for terms of service page
     */
    public function termsOfService(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessing terms of service page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Terms of Service',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * GET request for terms of service page
     */
    public function termsOfServiceWaagent(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessing terms of service page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Terms of Service (App)',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }
}
