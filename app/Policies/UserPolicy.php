<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Check if user has Super User permission
     */
    public function hasSuperPermission(User $user): ?bool
    {
        return $user->hasPermissionTo(User::SUPER) ? true : false;
    }
}
