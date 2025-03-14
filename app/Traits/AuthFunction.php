<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

trait AuthFunction
{
    /**
     * Private common function to check auth
     */
    private function checkAuthUser(array $validated): ?User
    {
        try {
            /** Attempt to login */
            $user = User::where('username', $validated['username'])->first();
            if (is_null($user)) {
                return null;
            }

            Log::debug('User Auth Check Data', ['user' => $user->username]);

            /** Check against password */
            $userCheckPassword = Hash::check($validated['password'], $user->password);

            /** Check if password or TOTP is correct */
            if (! $userCheckPassword) {
                return null;
            } else {
                return $user;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to check user', ['errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);

            return null;
        }
    }

    /**
     * Check if user is authenticated and logout
     */
    protected function checkAuthLogout(Request $request): void
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        /** Logout if user is authenticated */
        if (! is_null($user)) {
            Log::info('User logging out', ['userId' => $user?->id, 'userName' => $user?->name, 'route' => $request->route()->getName()]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            /** Also revoke all user token even if this is web routes */
            $user?->tokens->each(function ($token) {
                $token?->revoke();
            });
        }
    }
}
