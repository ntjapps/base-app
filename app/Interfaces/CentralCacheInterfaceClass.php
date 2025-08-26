<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Models\PermissionMenu;
use App\Models\PermissionScope;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CentralCacheInterfaceClass
{
    /**
     * Retrieve main menu from cache.
     *
     * This function retrieves all menu items from cache.
     * If cache is empty, it will generate all menu items and store in cache for 1 year.
     */
    public static function mainMenuCache(User $user): array
    {
        return Cache::remember(self::keyPermissionMenuItems($user->id), Carbon::now()->addYear(), function () {
            $menuArray = []; /** Menu Array */

            /** Top Order Menu */
            array_push($menuArray, MenuItemClass::dashboardMenu());

            /** Administration Menu */
            array_push($menuArray, MenuItemClass::administrationMenu());

            /** Bottom Order Menu */
            array_push($menuArray, MenuItemClass::logoutMenu());

            return array_filter($menuArray);
        });
    }

    /**
     * Retrieve a role from cache by name.
     *
     * This method retrieves a role from the cache using the role's name.
     * If the role is not present in the cache, it will be fetched from the database,
     * stored in the cache, and returned. The cached role is stored for 1 year.
     *
     * @param  User  $user  The user requesting the role.
     * @param  string  $role  The name of the role to retrieve.
     * @return Collection The role data as a collection.
     */
    public static function rememberRoleCache(string $role): Role
    {
        return Cache::remember(self::keyRoleName($role), Carbon::now()->addYear(), function () use ($role) {
            return Role::where('name', $role)->first();
        });
    }

    /**
     * Remove a role from cache by name.
     *
     * This method removes a role from the cache using the role's name.
     *
     * @param  string  $role  The name of the role to remove from the cache.
     */
    public static function forgetRoleCache(string $role): void
    {
        Cache::forget(self::keyRoleName($role));
    }

    /**
     * Remove all roles from cache.
     *
     * This method removes all roles from the cache.
     * It is used to flush the cache after modifying roles.
     *
     * @param  User  $user  The user requesting the roles to be removed from the cache.
     */
    public static function forgetAllRoleCache(User $user): void
    {
        if (config('cache.default') === 'redis') {
            $keys = Redis::keys(config('cache.prefix').Role::class.'-name-*');
            Redis::del($keys);
        } else {
            Log::warning('Cache driver is not redis');
        }
    }

    /**
     * Retrieve roles ordered by name from cache.
     *
     * This method retrieves all roles ordered by their type and name from the cache.
     * If the cache is empty, it fetches the roles from the database, stores them in the cache,
     * and returns them. The cached roles are stored for 1 year.
     *
     * @return EloquentCollection The collection of roles ordered by type and name.
     */
    public static function rememberRoleOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyRoleOrderByName(), Carbon::now()->addYear(), function () {
            return Role::orderBy('role_types')->orderBy('name')->get();
        });
    }

    /**
     * Retrieve permissions ordered by name from cache.
     *
     * This method retrieves all permissions ordered by their ability type and name from the cache.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of permissions ordered by ability type and name.
     */
    public static function rememberPermissionOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::with(['ability' => function ($query) {
                return $query->orderBy('title');
            }])->orderBy('ability_type')->get();
        });
    }

    /**
     * Retrieve permissions ordered by name from cache.
     *
     * This method retrieves all permissions ordered by their ability type and name from the cache,
     * except for permissions with ability type PermissionScope.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of permissions ordered by ability type and name.
     */
    public static function rememberPermissionConstOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionConstOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::with(['ability' => function ($query) {
                return $query->orderBy('title');
            }])->orderBy('ability_type')->get();
        });
    }

    /**
     * Retrieve permissions of type PermissionMenu ordered by name from cache.
     *
     * This method retrieves all permissions of type PermissionMenu ordered by their ability type and name from the cache.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of permissions of type PermissionMenu ordered by ability type and name.
     */
    public static function rememberPermissionMenuOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionMenuOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::where('ability_type', (string) PermissionMenu::class)->with(['ability' => function ($query) {
                return $query->orderBy('title');
            }])->orderBy('ability_type')->get();
        });
    }

    /*
     * Centralized cache key helpers to avoid collisions and provide a single source of truth
     */
    public static function keyPermissionMenuItems(int|string $userId): string
    {
        return 'permission:menu:items:'.$userId;
    }

    public static function keyRoleName(string $role): string
    {
        return 'role:name:'.$role;
    }

    public static function keyRoleOrderByName(): string
    {
        return 'role:orderBy:name';
    }

    public static function keyPermissionOrderByName(): string
    {
        return 'permission:orderBy:name';
    }

    public static function keyPermissionConstOrderByName(): string
    {
        return 'permission:const:orderBy:name';
    }

    public static function keyPermissionMenuOrderByName(): string
    {
        return 'permission:menu:orderBy:name';
    }

    public static function keyPermissionGetPermissionsByRole(int|string $roleId): string
    {
        return 'permission:getPermissionsByRole:'.$roleId;
    }

    public static function keyRoleGetRoles(int|string $userId): string
    {
        return 'role:getRoles:'.$userId;
    }

    public static function keyPermissionGetPermissions(int|string $userId): string
    {
        return 'permission:getPermissions:'.$userId;
    }

    public static function keyRoleSuperRoleId(): string
    {
        return 'role:superRoleId';
    }

    public static function keyPermissionSuperPermissionId(): string
    {
        return 'permission:superPermissionId';
    }

    public static function keyPermissionAbility(string $ability): string
    {
        return 'permission:ability:'.$ability;
    }

    public static function keyPermissionHasPermissionTo(int|string $permissionId, int|string $userId): string
    {
        return 'permission:hasPermissionTo:'.$permissionId.':user:'.$userId;
    }

    public static function keyPermissionName(string $name): string
    {
        return 'permission:name:'.$name;
    }

    public static function keyRabbitmqLock(string $taskName): string
    {
        return $taskName.'.rabbitmq.lock';
    }
}
