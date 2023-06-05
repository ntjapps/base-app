<?php

namespace App\Features;

use App\Models\User;

class DevSystem
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        return match (true) {
            $user?->hasPermissionTo(User::SUPER) => true,
            config('app.debug') => true,
            default => false,
        };
    }
}
