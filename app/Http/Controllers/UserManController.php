<?php

namespace App\Http\Controllers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\GoQueues;
use App\Interfaces\MenuItemClass;
use App\Interfaces\PermissionConstants;
use App\Interfaces\RoleConstants;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Traits\GoWorkerFunction;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET user management page
     */
    public function userManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open user role management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'User Role Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get user list from table
     */
    public function getUserList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get user list for User Role Management', $this->getLogContext($request, $user));

        $users = User::with(['roles' => function ($query) {
            return $query->orderBy('name');
        }, 'permissions' => function ($query) {
            return $query->orderBy('name');
        }])->orderBy('username')->get()->map(function (User $user) {
            return collect($user)->merge([
                'roles_array' => Cache::remember(CentralCacheInterfaceClass::keyRoleGetRoles($user->id), Carbon::now()->addYear(), function () use ($user) {
                    return $user->getRoleNames()->sortBy('name');
                }),
                'permissions_array' => Cache::remember(CentralCacheInterfaceClass::keyPermissionGetPermissions($user->id), Carbon::now()->addYear(), function () use ($user) {
                    return $user->getAllPermissions()->pluck('name')->sort()->values();
                }),
            ]);
        })->toArray();

        return response()->json([
            'data' => $users,
        ]);
    }

    /**
     * POST request to get roles and permissions form table
     */
    public function getUserRolePerm(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get user role and permission for User Role Management', $this->getLogContext($request, $user));

        return response()->json([
            'roles' => CentralCacheInterfaceClass::rememberRoleOrderByName(),
            'permissions' => CentralCacheInterfaceClass::rememberPermissionOrderByName(),
            'permissions_const' => CentralCacheInterfaceClass::rememberPermissionConstOrderByName(),
            'permissions_menu' => CentralCacheInterfaceClass::rememberPermissionMenuOrderByName(),
        ]);

    }

    /**
     * POST request user man submit
     */
    public function postUserManSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting submit user role and permission for User Role Management', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'id' => ['required_if:type_create,false', 'string', 'exists:App\Models\User,id'],
            'name' => ['required', 'string'],
            'username' => ['required', 'string'],
            'roles' => ['required_if:permissions,null', 'array'],
            'permissions' => ['required_if:roles,null', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('User submit user role and permission for User Role Management validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        /** Cannot assign Super Permission if not already have super role */
        $superRoleId = Cache::remember(CentralCacheInterfaceClass::keyRoleSuperRoleId(), Carbon::now()->addYear(), function () {
            return Role::where('name', RoleConstants::SUPER_ADMIN)->first()->id;
        });
        if (in_array($superRoleId, $validated['roles'] ?? [])) {
            Gate::forUser($user)->authorize('hasSuperPermission', User::class);
        }
        $superPermissionId = Cache::remember(CentralCacheInterfaceClass::keyPermissionSuperPermissionId(), Carbon::now()->addYear(), function () {
            // When polymorphic ability columns were removed, query by name directly
            return Permission::where('name', PermissionConstants::SUPER_ADMIN)->first()->id;
        });
        if (in_array($superPermissionId, $validated['permissions'] ?? [])) {
            Gate::forUser($user)->authorize('hasSuperPermission', User::class);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'user_create_or_update',
            payload: [
                'type_create' => $validated['type_create'],
                'id' => $validated['id'] ?? null,
                'name' => $validated['name'],
                'username' => $validated['username'],
                'roles' => $validated['roles'] ?? [],
                'permissions' => $validated['permissions'] ?? [],
                'default_password' => config('auth.defaults.reset_password_data'),
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('User create/update task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'User create/update task has been queued',
        ], 202);
    }

    /**
     * POST request delete user man submit
     */
    public function postDeleteUserManSubmit(Request $request, User $user): HttpJsonResponse
    {
        $userAuth = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting delete user for User Role Management', $this->getLogContext($request, $userAuth));

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'user_delete',
            payload: [
                'user_id' => $user->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('User delete task enqueued', $this->getLogContext($request, $userAuth, ['task_id' => $taskId, 'deleted_user_id' => $user->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'User deletion task has been queued',
        ], 202);
    }

    /**
     * POST request reset password user man submit
     */
    public function postResetPasswordUserManSubmit(Request $request, User $user): HttpJsonResponse
    {
        $userAuth = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting reset password user for User Role Management', $this->getLogContext($request, $userAuth));

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'user_reset_password',
            payload: [
                'user_id' => $user->id,
                'default_password' => config('auth.defaults.reset_password_data'),
                'invoker_id' => $userAuth->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('User password reset task enqueued', $this->getLogContext($request, $userAuth, ['task_id' => $taskId, 'user_id' => $user->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Password reset task has been queued',
        ], 202);
    }
}
