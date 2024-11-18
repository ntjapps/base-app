<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Models\Role;
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
     * List of user Permission
     */
    public const ALLPERM = [
        self::SUPER,
    ];

    public const PRIVILEGEPERM = [
        self::SUPER,
    ];

    public const SUPER = 'root';

    /**
     * Reset password to this password
     */
    public const RESETPASSWORD = 'reset';

    /**
     * List of user Roles
     */
    public const ALLROLE = [
        self::SUPERROLE,
    ];

    public const SUPERROLE = 'SU';

    public static function readApplicationVersion(): string
    {
        try {
            $file = file_get_contents(base_path('.constants'));
            foreach (explode("\n", $file) as $line) {
                if (strpos($line, 'APP_VERSION_HASH') !== false) {
                    return substr(str_replace('APP_VERSION_HASH=', '', $line), 0, 8);
                }
            }
        } catch (ErrorException $e) {
            return 'unknown';
        }
    }

    public static function flushRolePermissionCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::tags([Role::class])->flush();
        Cache::tags([Permission::class])->flush();
        Feature::flushCache();
    }
}
