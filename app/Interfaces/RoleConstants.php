<?php

namespace App\Interfaces;

/**
 * Centralized Role Constants and Hierarchy
 */
class RoleConstants
{
    // ========================================
    // ROLE NAMES
    // ========================================
    public const SUPER_ADMIN = 'super-admin';

    public const ADMIN = 'admin';

    public const MANAGER = 'manager';

    public const USER = 'user';

    public const GUEST = 'guest';

    // WhatsApp access role
    public const WHATSAPP_ACCESS = 'whatsapp-access';

    /**
     * Get all defined roles
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::MANAGER,
            self::USER,
            self::GUEST,
            self::WHATSAPP_ACCESS,
        ];
    }

    /**
     * Get role hierarchy with their default permissions
     * Roles inherit permissions from lower roles in the hierarchy
     */
    public static function hierarchy(): array
    {
        return [
            self::SUPER_ADMIN => [
                PermissionConstants::ALL, // Wildcard - all permissions
            ],

            self::ADMIN => [
                PermissionConstants::USER_ALL, // All user operations
                PermissionConstants::ROLE_VIEW,
                PermissionConstants::ROLE_EDIT,
                PermissionConstants::PERMISSION_VIEW,
                PermissionConstants::MENU_ALL, // All menu access
            ],

            self::MANAGER => [
                PermissionConstants::USER_VIEW,
                PermissionConstants::USER_EDIT,
                PermissionConstants::ROLE_VIEW,
                PermissionConstants::MENU_DASHBOARD,
                PermissionConstants::MENU_USERS,
                PermissionConstants::MENU_REPORTS,
            ],

            self::USER => [
                PermissionConstants::USER_VIEW,
                PermissionConstants::MENU_DASHBOARD,
            ],

            self::GUEST => [
                PermissionConstants::MENU_DASHBOARD,
            ],

            self::WHATSAPP_ACCESS => [
                PermissionConstants::WHATSAPP_VIEW,
                PermissionConstants::WHATSAPP_REPLY,
            ],
        ];
    }

    /**
     * Get permissions for a specific role
     */
    public static function permissions(string $role): array
    {
        return self::hierarchy()[$role] ?? [];
    }

    /**
     * Check if a role is privileged (system role)
     */
    public static function isPrivileged(string $role): bool
    {
        return in_array($role, [self::SUPER_ADMIN, self::ADMIN]);
    }
}
