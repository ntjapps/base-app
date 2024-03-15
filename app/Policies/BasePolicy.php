<?php

namespace App\Policies;

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
        $permission = Cache::tags([Permission::class])->remember(Permission::class.'-ability-'.InterfaceClass::SUPER, Carbon::now()->addYear(), function () {
            return Permission::where('name', InterfaceClass::SUPER)->first();
        });

        $hasPermissionToCache = Cache::tags([Permission::class])->remember(Permission::class.'-hasPermissionTo-'.$permission->id.'-user-'.$user->id, Carbon::now()->addYear(), function () use ($user, $permission) {
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
}
