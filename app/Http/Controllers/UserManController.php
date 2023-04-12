<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserManController extends Controller
{
    /**
     * GET user management page
     */
    public function userManPage(Request $request): View
    {
        Log::debug('User '.Auth::user()->name.' open user management page', ['user_id' => Auth::id(), 'remoteIp' => $request->ip()]);

        return view('super-pg.userman');
    }

    /**
     * POST request to get user list from table
     */
    public function getUserList(Request $request): JsonResponse
    {
        Log::debug('User '.Auth::guard('api')->user()->name.' get user list', ['user_id' => Auth::guard('api')->id(), 'apiUserIp' => $request->ip()]);

        $data = User::all();

        return response()->json($data);
    }
}
