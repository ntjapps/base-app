<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class DashController extends Controller
{
    use JsonResponse;

    /**
     * GET dashboard page
     */
    public function dashboardPage(Request $request): View
    {
        $userId = Auth::id() ?? Auth::guard('api')->id();
        $user = User::where('id', $userId)->first();
        Log::debug('User accessed dashboard page', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        return view('dash-pg.dashboard');
    }
}
