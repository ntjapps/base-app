<?php

namespace App\Interfaces;

use App\Models\Permission;
use App\Models\Role;
use App\Traits\CommonFunction;
use ErrorException;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class InterfaceClass implements PermissionConst, ResetPassConst, RoleConst
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

    public static function flushRolePermissionCache(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Cache::tags([Role::class])->flush();
        Cache::tags([Permission::class])->flush();
        Feature::flushCache();
    }
}
