<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user has Super User permission
     */
    public function hasSuperPermission(User $user): ?bool
    {
        return $user->hasPermissionTo(User::SUPER) ? true : false;
    }

    /**
     * Allow all action for any user
     */
    public function allowAllAction(User $user): bool
    {
        return ! is_null($user);
    }
}
