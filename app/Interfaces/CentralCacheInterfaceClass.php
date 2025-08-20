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
        return Cache::remember('permission:menu:items:'.$user->id, Carbon::now()->addYear(), function () {
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
        return Cache::remember('role:name:'.$role, Carbon::now()->addYear(), function () use ($role) {
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
        Cache::forget('role:name:'.$role);
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
        return Cache::remember('role:orderBy:name', Carbon::now()->addYear(), function () {
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
        return Cache::remember('permission:orderBy:name', Carbon::now()->addYear(), function () {
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
        return Cache::remember('permission:const:orderBy:name', Carbon::now()->addYear(), function () {
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
        return Cache::remember('permission:menu:orderBy:name', Carbon::now()->addYear(), function () {
            return Permission::where('ability_type', (string) PermissionMenu::class)->with(['ability' => function ($query) {
                return $query->orderBy('title');
            }])->orderBy('ability_type')->get();
        });
    }
}
