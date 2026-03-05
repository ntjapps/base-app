<?php

namespace App\Interfaces;

use App\Traits\CommonFunction;
use ErrorException;

class InterfaceClass
{
    use CommonFunction;

    public function __construct()
    {
        //
    }

    /**
     * Reset password to this password
     */
    public const RESETPASSWORD = 'reset';

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
        CentralCacheInterfaceClass::flushPermissions();
    }
}
