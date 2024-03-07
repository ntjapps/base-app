<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

class DevSystem
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        return match (true) {
            Gate::forUser($user)->allows('hasSuperPermission', User::class) => true,
            config('app.debug') => true,
            default => false,
        };
    }
}
