<?php

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Lottery;

class DevSystem
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(): bool
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('api')->user() ?? null;
        return match (true) {
            $user?->hasPermissionTo(User::SUPER) => true,
            config('app.debug') => true,
            default => false,
        };
    }
}
