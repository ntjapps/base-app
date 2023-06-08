<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
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

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'username' => ['nullable', 'string'],
            'name' => ['nullable', 'string'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $data = User::with(['permissions', 'roles'])
            ->when($validated['username'] ?? false, function ($query, $username) {
                $query->where('username', 'ILIKE', '%'.$username.'%');
            })->when($validated['name'] ?? false, function ($query, $name) {
                $query->where('name', 'ILIKE', '%'.$name.'%');
            })->get();

        return response()->json($data);
    }
}
