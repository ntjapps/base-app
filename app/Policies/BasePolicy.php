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
     * Check if user has a specific permission by ability title
     */
    protected function hasPermissionByAbility(User $user, string $abilityTitle): bool
    {
        $permission = Cache::remember(
            CentralCacheInterfaceClass::keyPermissionAbility($abilityTitle),
            Carbon::now()->addYear(),
            function () use ($abilityTitle) {
                return Permission::whereHas('ability', function ($query) use ($abilityTitle) {
                    return $query->where('title', $abilityTitle);
                })->first();
            }
        );

        if (! $permission) {
            return false;
        }

        return Cache::remember(
            CentralCacheInterfaceClass::keyPermissionHasPermissionTo($permission->id, $user->id),
            Carbon::now()->addYear(),
            fn () => $user->hasPermissionTo($permission)
        );
    }

    /**
     * Check if user has Super User permission
     */
    public function hasSuperPermission(User $user): bool
    {
        return $this->hasPermissionByAbility($user, InterfaceClass::SUPER);
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
