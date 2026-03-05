<?php

namespace Database\Seeders;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\PermissionConstants;
use App\Interfaces\RoleConstants;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        CentralCacheInterfaceClass::flushPermissions();

        // Create all permissions
        $this->command->info('Creating permissions...');
        foreach (PermissionConstants::all() as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => Permission::GUARD_NAME,
            ]);
            $this->command->info("  - {$permissionName}");
        }

        // Create roles and assign permissions
        $this->command->info("\nCreating roles and assigning permissions...");
        foreach (RoleConstants::hierarchy() as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => Permission::GUARD_NAME,
            ]);

            $role->syncPermissions($permissions);
            $this->command->info("  - {$roleName}: ".implode(', ', $permissions));
        }

        // Reset cache again
        CentralCacheInterfaceClass::flushPermissions();

        $this->command->info("\n✓ Permissions and roles seeded successfully!");
    }
}
