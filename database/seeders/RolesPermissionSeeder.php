<?php

namespace Database\Seeders;

use App\Jobs\RolePermissionSyncJob;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RolePermissionSyncJob::dispatchSync();
    }
}
