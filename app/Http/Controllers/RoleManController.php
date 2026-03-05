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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleManController extends Controller
{
    use GoWorkerFunction, JsonResponse, LogContext;

    /**
     * GET role management page
     */
    public function roleManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open Role Management page', $this->getLogContext($request, $user));

        return view('base-components.base', [
            'pageTitle' => 'Role Management',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get roles and permissions form table
     */
    public function getRoleList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting get role list for Role Management', $this->getLogContext($request, $user));

        $roles = Role::with(['permissions' => function ($query) {
            return $query->orderBy('name');
        }])->orderBy('name')->get()->map(function (Role $role) {
            return collect($role)->merge([
                'permissions_array' => Cache::remember(CentralCacheInterfaceClass::keyPermissionGetPermissionsByRole($role->id), Carbon::now()->addYear(), function () use ($role) {
                    return $role->getAllPermissions()->pluck('name')->sort()->values();
                }),
            ]);
        })->toArray();

        return response()->json([
            'data' => $roles,
        ]);
    }

    /**
     * POST request to add or modify roles
     */
    public function postRoleSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is submitting role data for Role Management', $this->getLogContext($request, $user));

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'role_name' => ['required_if:type_create,true', 'nullable', 'string', 'max:255', 'unique:App\Models\Role,name'],
            'role_id' => ['required_if:type_create,false', 'nullable', 'string', 'exists:App\Models\Role,id'],
            'role_rename' => ['required_if:type_create,false', 'nullable', 'string', 'max:255'],
            'permissions' => ['required', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        // Secondary validation for role_rename unique check
        if (! $validated['type_create'] && ! empty($validated['role_rename'])) {
            $uniqueCheck = Validator::make($validated, [
                'role_rename' => [Rule::unique(Role::class, 'name')->ignore($validated['role_id'])],
            ]);
            if ($uniqueCheck->fails()) {
                throw new ValidationException($uniqueCheck);
            }
        }

        $validateLog = $validated;
        Log::info('User is submitting role data for Role Management', $this->getLogContext($request, $user, ['validated' => json_encode($validateLog)]));

        $roleName = $validated['role_name'] ?? $validated['role_rename'] ?? null;
        $roleId = $validated['role_id'] ?? null;

        /** Cannot Create or Modify Super Role and Admin Role */
        $rolesSuper = Cache::remember(CentralCacheInterfaceClass::keyRoleName(RoleConstants::SUPER_ADMIN), Carbon::now()->addYear(), function () {
            return Role::where('name', RoleConstants::SUPER_ADMIN)->first();
        });
        if ($roleId === $rolesSuper->id) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }
        if ($roleName === RoleConstants::SUPER_ADMIN) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }

        /** Cannot Add Admin or Super Permission */
        $superPermissionId = Cache::remember(CentralCacheInterfaceClass::keyPermissionName(PermissionConstants::SUPER_ADMIN), Carbon::now()->addYear(), function () {
            // When polymorphic ability columns were removed, query by name directly
            return Permission::where('name', PermissionConstants::SUPER_ADMIN)->first()->id;
        });
        if (in_array($superPermissionId, $validated['permissions'])) {
            Gate::forUser($user)->authorize('hasSuperPermission', User::class);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'role_create_or_update',
            payload: [
                'type_create' => $validated['type_create'],
                'role_id' => $roleId,
                'role_name' => $roleName,
                'permissions' => $validated['permissions'],
            ],
            queue: GoQueues::ADMIN
        );

        Log::notice('Role create/update task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Role create/update task has been queued',
        ], 202);
    }

    /**
     * POST request to delete role
     */
    public function postDeleteRoleSubmit(Request $request, Role $role): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting delete role for Role Management', $this->getLogContext($request, $user));

        /** Check if Role Renamed is in Const list */
        if ($role->name === RoleConstants::SUPER_ADMIN) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }

        // Delegate to Go worker
        $taskId = $this->sendGoTask(
            task: 'role_delete',
            payload: [
                'role_id' => $role->id,
            ],
            queue: GoQueues::ADMIN
        );

        Log::warning('Role delete task enqueued', $this->getLogContext($request, $user, ['task_id' => $taskId, 'deleted_role_id' => $role->id]));

        return response()->json([
            'task_id' => $taskId,
            'status' => 'queued',
            'message' => 'Role deletion task has been queued',
        ], 202);
    }
}
