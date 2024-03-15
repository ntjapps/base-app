<?php

namespace App\Observers;

use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use App\Models\PermissionPrivilege;

class PermissionPrivilegeObserver
{
    /**
     * Handle events after all transactions are committed.
     *
     * @var bool
     */
    public $afterCommit = true;

    /**
     * Handle the PermissionPrivilege "created" event.
     */
    public function created(PermissionPrivilege $permissionPrivilege): void
    {
        Permission::firstOrCreate([
            'name' => $permissionPrivilege->id,
            'guard_name' => (new Permission())->guard_name,
            'ability_type' => PermissionPrivilege::class,
            'ability_id' => $permissionPrivilege->id,
        ]);

        InterfaceClass::flushRolePermissionCache();
    }

    /**
     * Handle the PermissionPrivilege "force deleted" event.
     */
    public function forceDeleted(PermissionPrivilege $permissionPrivilege): void
    {
        Permission::where([
            'ability_type' => PermissionPrivilege::class,
            'ability_id' => $permissionPrivilege->id,
        ])->delete();

        InterfaceClass::flushRolePermissionCache();
    }
}
