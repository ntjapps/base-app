<?php

namespace App\Policies;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\PermissionConstants;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait BasePolicy
{
    /**
     * Check if user has a specific permission by name
     */
    protected function hasPermission(User $user, string $permissionName): bool
    {
        return Cache::remember(
            CentralCacheInterfaceClass::keyPermissionHasPermissionTo($permissionName, $user->id),
            Carbon::now()->addYear(),
            fn () => $user->hasPermissionTo($permissionName)
        );
    }

    /**
     * Check if user has Super Admin permission
     */
    public function hasSuperPermission(User $user): bool
    {
        return $this->hasPermission($user, PermissionConstants::SUPER_ADMIN);
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
