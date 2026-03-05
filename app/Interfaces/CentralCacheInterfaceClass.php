<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Pennant\Feature;
use Spatie\Permission\PermissionRegistrar;

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

            /** WhatsApp Menu */
            array_push($menuArray, MenuItemClass::whatsappMenu());

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
            if (! empty($keys)) {
                Redis::del($keys);
            }
        } else {
            Log::warning('Cache driver is not redis');
        }
    }

    /**
     * Flush all permission and role related caches.
     */
    public static function flushPermissions(): void
    {
        // Clear Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Also explicitly forget Spatie permission cache key to ensure removal across drivers
        Cache::forget(config('permission.cache.key'));

        if (config('cache.default') === 'redis') {
            try {
                // Use the 'cache' connection since Laravel Cache uses it
                $redis = Redis::connection('cache');
                $prefix = config('cache.prefix');

                // Retrieve the Redis prefix from configuration (preferred) or client option
                $redisPrefix = config('database.redis.options.prefix');
                if ($redisPrefix === null) {
                    try {
                        $redisPrefix = $redis->getOption(\Redis::OPT_PREFIX) ?? '';
                    } catch (\Throwable $e) {
                        $redisPrefix = '';
                    }
                }

                $patterns = [
                    $prefix.'permission*',
                    $prefix.'role*',
                ];

                foreach ($patterns as $pattern) {
                    // keys() returns full keys including the Redis prefix
                    $keys = $redis->keys($pattern);
                    if (! empty($keys)) {
                        // Strip the Redis prefix from keys because $redis->del() will automatically prepend it
                        if ($redisPrefix) {
                            $len = strlen($redisPrefix);
                            $keys = array_map(function ($key) use ($redisPrefix, $len) {
                                if (substr($key, 0, $len) === $redisPrefix) {
                                    return substr($key, $len);
                                }

                                return $key;
                            }, $keys);
                        }

                        foreach (array_chunk($keys, 100) as $chunk) {
                            $redis->del($chunk);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Unable to flush permission-related redis keys: '.$e->getMessage());
            }
        } else {
            // Fallback: clear specific known keys
            Cache::forget(self::keyPermissionOrderByName());
            Cache::forget(self::keyPermissionConstOrderByName());
            Cache::forget(self::keyPermissionMenuOrderByName());
            Cache::forget(self::keyRoleOrderByName());
            Cache::forget(self::keyRoleSuperRoleId());
            Cache::forget(self::keyPermissionSuperPermissionId());

            // Ensure per-user role/permission caches are also cleared for non-redis cache drivers.
            // Iterate users in chunks to avoid loading the entire table into memory.
            try {
                self::forgetAllUserRolePermissionCaches();
            } catch (\Exception $e) {
                Log::warning('Unable to forget per-user role/permission caches: '.$e->getMessage());
            }
        }

        if (class_exists(Feature::class)) {
            Feature::flushCache();
        }
    }

    /**
     * Remove user roles and permissions from cache.
     *
     * @param  int|string  $userId  The user ID.
     */
    public static function forgetUserRolePermissionCache(int|string $userId): void
    {
        Cache::forget(self::keyRoleGetRoles($userId));
        Cache::forget(self::keyPermissionGetPermissions($userId));
        Cache::forget(self::keyPermissionMenuItems($userId));
    }

    /**
     * Remove per-user role and permission caches for all users.
     * Uses chunking to avoid loading all users into memory at once.
     */
    public static function forgetAllUserRolePermissionCaches(): void
    {
        User::chunkById(100, function ($users) {
            foreach ($users as $u) {
                self::forgetUserRolePermissionCache($u->id);
            }
        });
    }

    /**
     * Flush all caches (Application, Spatie Permission, Redis keys, Features).
     */
    public static function flushAllCache(): void
    {
        // Clear Spatie permission cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Ensure Spatie permission key is removed from application cache
        Cache::forget(config('permission.cache.key'));

        // Flush application cache
        Cache::flush();

        // If using Redis, attempt to delete all keys under cache prefix
        if (config('cache.default') === 'redis') {
            try {
                // Use the 'cache' connection since Laravel Cache usually uses it
                $redis = Redis::connection('cache');
                $prefix = config('cache.prefix');
                $keys = $redis->keys($prefix.'*');

                if (! empty($keys)) {
                    foreach (array_chunk($keys, 100) as $chunk) {
                        $redis->del($chunk);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Unable to flush redis keys: '.$e->getMessage());
            }
        }

        if (class_exists(Feature::class)) {
            Feature::flushCache();
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
     * This method retrieves all permissions ordered by their name from the cache.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of permissions ordered by name.
     */
    public static function rememberPermissionOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::orderBy('name')->get();
        });
    }

    /**
     * Retrieve permissions ordered by name from cache.
     *
     * This method retrieves all permissions ordered by their name from the cache.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of permissions ordered by name.
     */
    public static function rememberPermissionConstOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionConstOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::orderBy('name')->get();
        });
    }

    /**
     * Retrieve menu permissions ordered by name from cache.
     *
     * This method retrieves all permissions with 'menu.' prefix ordered by name from the cache.
     * If the cache is empty, it fetches the permissions from the database, stores them in the cache,
     * and returns them. The cached permissions are stored for 1 year.
     *
     * @return EloquentCollection The collection of menu permissions ordered by name.
     */
    public static function rememberPermissionMenuOrderByName(): EloquentCollection
    {
        return Cache::remember(self::keyPermissionMenuOrderByName(), Carbon::now()->addYear(), function () {
            return Permission::where('name', 'LIKE', 'menu.%')->orderBy('name')->get();
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

    /**
     * Cache key for permission check
     *
     * @param  int|string  $permissionNameOrId  Permission name (string) or ID (for backward compat)
     * @param  int|string  $userId  User UUID
     */
    public static function keyPermissionHasPermissionTo(int|string $permissionNameOrId, int|string $userId): string
    {
        return 'permission:hasPermissionTo:'.$permissionNameOrId.':user:'.$userId;
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
