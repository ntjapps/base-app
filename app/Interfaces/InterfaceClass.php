<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Traits\CommonFunction;
use ErrorException;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class InterfaceClass
{
    use CommonFunction;

    public function __construct()
    {
        //
    }

    /**
     * List of user Permission (backward compatibility)
     *
     * @deprecated Use PermissionConstants instead
     */
    public const ALLPERM = [
        self::SUPER,
    ];

    /**
     * @deprecated Use PermissionConstants::privileged() instead
     */
    public const PRIVILEGEPERM = [
        self::SUPER,
    ];

    /**
     * Super admin permission (backward compatibility)
     *
     * @deprecated Use PermissionConstants::SUPER_ADMIN instead
     */
    public const SUPER = PermissionConstants::SUPER_ADMIN;

    /**
     * Reset password to this password
     */
    public const RESETPASSWORD = 'reset';

    /**
     * List of user Roles (backward compatibility)
     *
     * @deprecated Use RoleConstants instead
     */
    public const ALLROLE = [
        self::SUPERROLE,
    ];

    /**
     * Super admin role (backward compatibility)
     *
     * @deprecated Use RoleConstants::SUPER_ADMIN instead
     */
    public const SUPERROLE = RoleConstants::SUPER_ADMIN;

    public static function readApplicationVersion(): string
    {
        try {
            $file = file_get_contents(base_path('.constants'));
            foreach (explode("\n", $file) as $line) {
                if (strpos($line, 'APP_VERSION_HASH') !== false) {
                    return substr(str_replace('APP_VERSION_HASH=', '', $line), 0, 8);
                }
            }

            return 'unknown';
        } catch (ErrorException $e) {
            return 'unknown';
        }
    }

    public static function flushRolePermissionCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::flush();
        Cache::flush();
        Feature::flushCache();
    }
}
