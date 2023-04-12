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
        return [
            'label' => 'Logout',
            'icon' => 'pi pi-power-off',
            'url' => parse_url(route('get-logout'), PHP_URL_PATH),
        ];
    }

    public static function administrationMenu(): array
    {
        $childMenu = [];
        $user = Auth::guard('api')->user() ?? Auth::guard('api')->user();

        if (Gate::forUser($user)->allows('hasSuperPermission', User::class)) {

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

        return [
            'label' => 'Administration',
            'icon' => 'pi pi-cog',
            'items' => $childMenu,
        ];
    }
}
