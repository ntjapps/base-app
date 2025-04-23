<?php

namespace App\Http\Controllers;

use App\Interfaces\InterfaceClass;
use App\Interfaces\MenuItemClass;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Traits\JsonResponse;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleManController extends Controller
{
    use JsonResponse;

    /**
     * GET role management page
     */
    public function roleManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User open Role Management page', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

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
        Log::debug('User is requesting get role list for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

        $data = Role::with(['permissions' => function ($query) {
            return $query->orderBy('name');
        }])->orderBy('name')->get()->map(function (Role $role) {
            return collect($role)->merge([
                'permissions_array' => Cache::remember(Permission::class.'-getPermissionsByRole-'.$role->id, Carbon::now()->addYear(), function () use ($role) {
                    return Permission::with('ability')->whereIn('id', $role->getAllPermissions()->pluck('id'))->orderBy('ability_type')->get()->pluck('ability')->pluck('title');
                }),
            ]);
        });

        return response()->json($data);
    }

    /**
     * POST request to add or modify roles
     */
    public function postRoleSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is submitting role data for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'type_create' => ['required', 'boolean'],
            'role_name' => ['required_if:type_create,true', 'nullable', 'string', 'max:255', 'unique:App\Models\Role,name'],
            'role_id' => ['required_if:type_create,false', 'nullable', 'string', 'exists:App\Models\Role,id'],
            'role_rename' => ['required_if:type_create,false', 'nullable', 'string', 'max:255', Rule::unique(Role::class, 'name')->ignore($request->input('role_id'))],
            'permissions' => ['required', 'array'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validateLog = $validated;
        Log::info('User is submitting role data for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validateLog)]);

        $roleName = $validated['role_name'] ?? $validated['role_rename'] ?? null;
        $roleId = $validated['role_id'] ?? null;

        /** Cannot Create or Modify Super Role and Admin Role */
        $rolesSuper = Cache::remember(Role::class.'-name-'.InterfaceClass::SUPERROLE, Carbon::now()->addYear(), function () {
            return Role::where('name', InterfaceClass::SUPERROLE)->first();
        });
        if ($roleId === $rolesSuper->id) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }
        if ($roleName === InterfaceClass::SUPERROLE) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }

        /** Cannot Add Admin or Super Permission */
        $superPermissionId = Cache::remember(Permission::class.'-name-'.InterfaceClass::SUPER, Carbon::now()->addYear(), function () {
            return Permission::whereHas('ability', function ($query) {
                return $query->where('title', InterfaceClass::SUPER);
            })->first()->id;
        });
        if (in_array($superPermissionId, $validated['permissions'])) {
            Gate::forUser($user)->authorize('hasSuperPermission', User::class);
        }

        DB::beginTransaction();
        try {
            if ($validated['type_create']) {
                $role = Role::create(['name' => $roleName]);
            } else {
                $role = Role::where('id', $roleId)->first();
                if (is_null($role)) {
                    throw new ModelNotFoundException;
                }

                if ($role->name !== $roleName) {
                    /** Check if Role Renamed is in Const list */
                    if (in_array($role->name, InterfaceClass::ALLROLE)) {
                        Gate::forUser($user)->authorize('denyAllAction', User::class);
                    }

                    $role->name = $roleName;
                    $role->save();
                }
            }

            /** Updated Permission Array */
            (array) $permissionNames = Permission::whereIn('name', $validated['permissions'])->get()->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);

            DB::commit();

            InterfaceClass::flushRolePermissionCache();

            Log::notice('User successfully submitted role data for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('User is submitting role data for Role Management failed', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'error' => $e->getMessage()]);
            throw $e;
        }

        return $this->jsonSuccess('Role data has been submitted successfully', 'Role data has been submitted successfully');
    }

    /**
     * POST request to delete role
     */
    public function postDeleteRoleSubmit(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting delete role for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

        /** Validate Request */
        $validate = Validator::make($request->all(), [
            'id' => ['required', 'string', 'exists:App\Models\Role,id'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validateLog = $validated;
        Log::info('User is submitting delete role for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName(), 'validated' => json_encode($validateLog)]);

        $role = Role::where('id', $validated['id'])->first();

        /** Check if Role Renamed is in Const list */
        if (in_array($role->name, InterfaceClass::ALLROLE)) {
            Gate::forUser($user)->authorize('denyAllAction', User::class);
        }

        $role->delete();

        Log::warning('User successfully delete role for Role Management', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

        return $this->jsonSuccess('Role deleted successfully', 'Role deleted successfully');
    }
}
