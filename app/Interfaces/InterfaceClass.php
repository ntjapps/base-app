<?php

namespace App\Interfaces;

use App\Traits\CommonFunction;
use ErrorException;

class InterfaceClass implements CacheKeyConst, PermissionConst, RoleConst, ResetPassConst
{
    use CommonFunction;

    public function __construct()
    {
        //
    }

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
}
