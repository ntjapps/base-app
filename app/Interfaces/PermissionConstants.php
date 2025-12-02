<?php

namespace App\Interfaces;

/**
 * Centralized Permission Constants
 * Using namespaced naming convention for clear categorization
 */
class PermissionConstants
{
    // ========================================
    // SUPER ADMIN / ROOT PERMISSIONS
    // ========================================
    public const SUPER_ADMIN = 'super.admin';

    // ========================================
    // USER MANAGEMENT PERMISSIONS
    // ========================================
    public const USER_VIEW = 'user.view';

    public const USER_CREATE = 'user.create';

    public const USER_EDIT = 'user.edit';

    public const USER_DELETE = 'user.delete';

    public const USER_MANAGE = 'user.manage'; // All user operations

    // ========================================
    // ROLE MANAGEMENT PERMISSIONS
    // ========================================
    public const ROLE_VIEW = 'role.view';

    public const ROLE_CREATE = 'role.create';

    public const ROLE_EDIT = 'role.edit';

    public const ROLE_DELETE = 'role.delete';

    public const ROLE_MANAGE = 'role.manage'; // All role operations

    // ========================================
    // PERMISSION MANAGEMENT
    // ========================================
    public const PERMISSION_VIEW = 'permission.view';

    public const PERMISSION_ASSIGN = 'permission.assign';

    public const PERMISSION_MANAGE = 'permission.manage';

    // ========================================
    // MENU PERMISSIONS (UI Navigation)
    // ========================================
    public const MENU_DASHBOARD = 'menu.dashboard';

    public const MENU_USERS = 'menu.users';

    public const MENU_ROLES = 'menu.roles';

    public const MENU_SETTINGS = 'menu.settings';

    public const MENU_REPORTS = 'menu.reports';

    public const MENU_SYSTEM = 'menu.system';

    // ========================================
    // WHATSAPP PERMISSIONS
    // ========================================
    public const WHATSAPP_VIEW = 'whatsapp.view';

    public const WHATSAPP_REPLY = 'whatsapp.reply';

    // ========================================
    // WILDCARD GROUPS
    // ========================================
    public const ALL = '*'; // God mode - all permissions

    public const USER_ALL = 'user.*'; // All user operations

    public const ROLE_ALL = 'role.*'; // All role operations

    public const MENU_ALL = 'menu.*'; // All menu access

    public const PERMISSION_ALL = 'permission.*'; // All permission operations

    /**
     * Get all defined permissions
     */
    public static function all(): array
    {
        return [
            // Super admin
            self::SUPER_ADMIN,

            // User management
            self::USER_VIEW,
            self::USER_CREATE,
            self::USER_EDIT,
            self::USER_DELETE,
            self::USER_MANAGE,

            // Role management
            self::ROLE_VIEW,
            self::ROLE_CREATE,
            self::ROLE_EDIT,
            self::ROLE_DELETE,
            self::ROLE_MANAGE,

            // Permission management
            self::PERMISSION_VIEW,
            self::PERMISSION_ASSIGN,
            self::PERMISSION_MANAGE,

            // Menu permissions
            self::MENU_DASHBOARD,
            self::MENU_USERS,
            self::MENU_ROLES,
            self::MENU_SETTINGS,
            self::MENU_REPORTS,
            self::MENU_SYSTEM,

            // WhatsApp permissions
            self::WHATSAPP_VIEW,
            self::WHATSAPP_REPLY,

            // Wildcard permissions (must be created in DB for Spatie to recognize them)
            self::ALL,
            self::USER_ALL,
            self::ROLE_ALL,
            self::MENU_ALL,
            self::PERMISSION_ALL,
        ];
    }

    /**
     * Get privileged permissions (backward compatibility)
     */
    public static function privileged(): array
    {
        return [
            self::SUPER_ADMIN,
            self::USER_MANAGE,
            self::ROLE_MANAGE,
            self::PERMISSION_MANAGE,
        ];
    }

    /**
     * Get menu permissions
     */
    public static function menus(): array
    {
        return [
            self::MENU_DASHBOARD,
            self::MENU_USERS,
            self::MENU_ROLES,
            self::MENU_SETTINGS,
            self::MENU_REPORTS,
            self::MENU_SYSTEM,
            // Note: WhatsApp menu is gated by whatsapp.view permission
            // It is not listed under menu.* namespace to keep existing patterns
        ];
    }

    /**
     * Get permissions by namespace
     */
    public static function byNamespace(string $namespace): array
    {
        return array_filter(self::all(), fn ($perm) => str_starts_with($perm, $namespace.'.'));
    }
}
