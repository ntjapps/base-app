<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MenuItemClass
{
    /**
     * Get current route expanded keys
     */
    public static function currentRouteExpandedKeys(string $getName): ?string
    {
        /** Case for route name */
        switch ($getName) {
            case 'profile':
                $expandedKeys = '9999';
                break;
            case 'user-man':
                $expandedKeys = '9999';
                break;
            case 'role-man':
                $expandedKeys = '9999';
                break;
            case 'passport-man':
                $expandedKeys = '9999';
                break;
            case 'whatsapp-man':
                $expandedKeys = '9999';
            case 'server-logs':
                $expandedKeys = '9999';
                break;
            default:
                $expandedKeys = null;
                break;
        }

        return $expandedKeys;
    }

    public static function dashboardMenu(): array
    {
        return [
            'label' => 'Dashboard',
            'icon' => 'pi pi-home',
            'url' => parse_url(route('dashboard'), PHP_URL_PATH),
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

        array_push($childMenu, [
            'label' => 'Edit Profile',
            'icon' => 'pi pi-user-edit',
            'url' => parse_url(route('profile'), PHP_URL_PATH),
        ]);

        if (Gate::forUser($user)->allows('hasSuperPermission', User::class)) {

            array_push($childMenu, [
                'label' => 'User Management',
                'icon' => 'pi pi-users',
                'url' => parse_url(route('user-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Role Management',
                'icon' => 'pi pi-briefcase',
                'url' => parse_url(route('role-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Passport Management',
                'icon' => 'pi pi-key',
                'url' => parse_url(route('passport-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'WhatsApp Management',
                'icon' => 'pi pi-whatsapp',
                'url' => parse_url(route('whatsapp-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Server Logs',
                'icon' => 'pi pi-server',
                'url' => parse_url(route('server-logs'), PHP_URL_PATH),
            ]);
        }

        if (empty($childMenu)) {
            return [];
        }

        return [
            'key' => '9999',
            'label' => 'Administration',
            'icon' => 'pi pi-cog',
            'items' => $childMenu,
        ];
    }
}
