<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
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
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open user management page', ['userId' => $user?->id, 'userName' => $user?->name, 'remoteIp' => $request->ip()]);

        return view('super-pg.userman');
    }

    /**
     * POST request to get user list from table
     */
    public function getUserList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User get user list', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

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

    /**
     * POST request to get roles and permissions form table
     */
    public function getUserRolePerm(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get user role and permission for User Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'apiUserIp' => $request->ip()]);

        $data['roles'] = Role::all()->pluck('name')->toArray();
        $data['permissions'] = Permission::all()->pluck('name')->toArray();

        return response()->json($data);
    }
}
