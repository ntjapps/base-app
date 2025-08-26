<?php

namespace App\Policies;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait BasePolicy
{
    /**
     * Check if user has Super User permission
     */
    public function hasSuperPermission(User $user): ?bool
    {
        $permission = Cache::remember(CentralCacheInterfaceClass::keyPermissionAbility(InterfaceClass::SUPER), Carbon::now()->addYear(), function () {
            return Permission::whereHas('ability', function ($query) {
                return $query->where('title', InterfaceClass::SUPER);
            })->first();
        });

        $hasPermissionToCache = Cache::remember(CentralCacheInterfaceClass::keyPermissionHasPermissionTo($permission->id, $user->id), Carbon::now()->addYear(), function () use ($user, $permission) {
            return $user->hasPermissionTo($permission);
        });

        return $hasPermissionToCache ? true : false;
    }

    /**
     * Allow all action for any user
     */
    public function allowAllAction(User $user): bool
    {
        return ! is_null($user);
    }

    /**
     * Deny all action for any user
     */
    public function denyAllAction(): bool
    {
        return false;
    }
}
