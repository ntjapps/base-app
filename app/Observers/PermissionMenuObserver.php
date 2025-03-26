<?php

namespace App\Observers;

use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use App\Models\PermissionMenu;

class PermissionMenuObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the PermissionMenu "created" event.
     */
    public function created(PermissionMenu $permissionMenu): void
    {
        Permission::firstOrCreate([
            'name' => $permissionMenu->id,
            'guard_name' => Permission::GUARD_NAME,
            'ability_type' => PermissionMenu::class,
            'ability_id' => $permissionMenu->id,
        ]);

        InterfaceClass::flushRolePermissionCache();
    }

    /**
     * Handle the PermissionMenu "force deleted" event.
     */
    public function forceDeleted(PermissionMenu $permissionMenu): void
    {
        Permission::where([
            'ability_type' => PermissionMenu::class,
            'ability_id' => $permissionMenu->id,
        ])->delete();

        InterfaceClass::flushRolePermissionCache();
    }
}
