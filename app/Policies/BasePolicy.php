<?php

namespace App\Policies;

use App\Interfaces\InterfaceClass;
use App\Models\User;

trait BasePolicy
{
    /**
     * Check if user has Super User permission
     */
    public function hasSuperPermission(User $user): ?bool
    {
        return $user->hasPermissionTo(InterfaceClass::SUPER) ? true : false;
    }

    /**
     * Allow all action for any user
     */
    public function allowAllAction(User $user): bool
    {
        return ! is_null($user);
    }
}
