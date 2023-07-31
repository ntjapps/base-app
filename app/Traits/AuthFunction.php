<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OTPHP\TOTP;

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
            Log::debug('User Auth Check Data', ['user' => $user?->username]);
        } catch (\Throwable $e) {
            Log::error('Failed to check user', ['exception' => $e]);

            return null;
        }

        /** Check against password */
        $userCheckPassword = Hash::check($validated['password'], $user?->password);
        
        /** Check against TOTP */
        $userCheckTotp = TOTP::create($user?->totp_key)->now() == $validated['password'];

        /** Check if password or TOTP is correct */
        if (!$userCheckPassword && !$userCheckTotp) {
            return null;
        } else {
            return $user;
        }
    }

    /**
     * Check if user is authenticated and logout
     */
    protected function checkAuthLogout(Request $request): void
    {
        /** Logout if user is authenticated */
        if (Auth::check()) {
            Log::info('User '.Auth::user()?->name.' logging out', ['userId' => Auth::user()?->id]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
