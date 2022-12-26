<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

trait MenuItemConst
{
    public static function dashboardMenu(): array
    {
        return [
            'label' => 'Dashboard',
            'icon' => 'pi pi-home',
            'url' => parse_url(route('dashboard'), PHP_URL_PATH),
        ];
    }

    public static function editProfileMenu(): array
    {
        return [
            'label' => 'Edit Profile',
            'icon' => 'pi pi-user-edit',
            'url' => parse_url(route('profile'), PHP_URL_PATH),
        ];
    }

    public static function logoutMenu(): array
    {
        if (Auth::guard('sanctum')->check()) {
            return [
                'label' => 'Logout',
                'icon' => 'pi pi-power-off',
                'url' => parse_url(route('post-token-revoke'), PHP_URL_PATH),
            ];
        } else {
            return [
                'label' => 'Logout',
                'icon' => 'pi pi-power-off',
                'url' => parse_url(route('get-logout'), PHP_URL_PATH),
            ];
        }
    }

    private static function administrationChildMenu(): array
    {
        $childMenu = [];

        if (Gate::allows('hasSuperPermission', User::class)) {
            $childMenu[] = [
                'label' => 'Server Queue - Horizon',
                'icon' => 'pi pi-bolt',
                'url' => parse_url(route('horizon.index'), PHP_URL_PATH),
            ];

            $childMenu[] = [
                'label' => 'Server Logs',
                'icon' => 'pi pi-server',
                'url' => parse_url(route('server-logs'), PHP_URL_PATH),
            ];

            $childMenu[] = [
                'label' => 'User Management',
                'icon' => 'pi pi-users',
                'url' => parse_url(route('user-man'), PHP_URL_PATH),
            ];
        }

        return $childMenu;
    }

    public static function administrationMenu(): array
    {
        return [
            'label' => 'Administration',
            'icon' => 'pi pi-cog',
            'items' => self::administrationChildMenu(),
        ];
    }
}
