<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Menu item builder class for NuxtUI NavigationMenu component.
 *
 * Returns menu items in the format expected by NuxtUI NavigationMenu:
 * - 'label': Display text (required)
 * - 'icon': Icon class string
 * - 'href': Link/route path
 * - 'children': Array of nested menu items (not 'items')
 * - 'key': Optional identifier for expanded state tracking
 */
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
            case 'division-man':
                $expandedKeys = '9999';
                break;
            case 'tag-man':
                $expandedKeys = '9999';
                break;
            case 'ai-model-instruction-man':
                $expandedKeys = '9999';
                break;
            case 'whatsapp-man':
                $expandedKeys = '1000';
                break;
            case 'whatsapp-templates-man':
                $expandedKeys = '1000';
                break;
            case 'route-analytics':
                $expandedKeys = '9999';
                break;
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
            'icon' => 'i-heroicons-home',
            'href' => parse_url(route('dashboard'), PHP_URL_PATH),
        ];
    }

    public static function logoutMenu(): array
    {
        return [
            'label' => 'Logout',
            'icon' => 'i-heroicons-power',
            'href' => route('get-logout'), // Full URL with domain forces native navigation
            'target' => '_self',
        ];
    }

    public static function whatsappMenu(): array
    {
        $user = Auth::guard('api')->user() ?? Auth::guard('api')->user();
        // WhatsApp menu should only be visible to users with whatsapp.view permission
        if (! Gate::forUser($user)->allows('hasPermission', PermissionConstants::WHATSAPP_VIEW)) {
            return [];
        }

        return [
            'key' => '1000',
            'label' => 'WhatsApp',
            'icon' => 'i-heroicons-chat-bubble-oval-left-ellipsis',
            'children' => [
                [
                    'label' => 'Inbox',
                    'icon' => 'i-heroicons-inbox',
                    'href' => parse_url(route('whatsapp-man'), PHP_URL_PATH),
                ],
                [
                    'label' => 'Templates',
                    'icon' => 'i-heroicons-document-text',
                    'href' => parse_url(route('whatsapp-templates-man'), PHP_URL_PATH),
                ],
            ],
        ];
    }

    public static function administrationMenu(): array
    {
        $childMenu = [];
        $user = Auth::guard('api')->user() ?? Auth::guard('api')->user();

        array_push($childMenu, [
            'label' => 'Edit Profile',
            'icon' => 'i-heroicons-user-circle',
            'href' => parse_url(route('profile'), PHP_URL_PATH),
        ]);

        if (Gate::forUser($user)->allows('hasSuperPermission', User::class)) {

            array_push($childMenu, [
                'label' => 'User Management',
                'icon' => 'i-heroicons-users',
                'href' => parse_url(route('user-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Role Management',
                'icon' => 'i-heroicons-briefcase',
                'href' => parse_url(route('role-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Passport Management',
                'icon' => 'i-heroicons-key',
                'href' => parse_url(route('passport-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Division Management',
                'icon' => 'i-heroicons-rectangle-group',
                'href' => parse_url(route('division-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Tag Management',
                'icon' => 'i-heroicons-tag',
                'href' => parse_url(route('tag-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'AI Model Instructions',
                'icon' => 'i-heroicons-cpu-chip',
                'href' => parse_url(route('ai-model-instruction-man'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Route Analytics',
                'icon' => 'i-heroicons-chart-bar',
                'href' => parse_url(route('route-analytics'), PHP_URL_PATH),
            ]);

            array_push($childMenu, [
                'label' => 'Server Logs',
                'icon' => 'i-heroicons-server-stack',
                'href' => parse_url(route('server-logs'), PHP_URL_PATH),
            ]);
        }

        if (empty($childMenu)) {
            return [];
        }

        return [
            'key' => '9999',
            'label' => 'Administration',
            'icon' => 'i-heroicons-cog-6-tooth',
            'children' => $childMenu,
        ];
    }
}
