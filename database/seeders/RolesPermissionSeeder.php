<?php

namespace Database\Seeders;

use App\Jobs\RolePermissionSyncJob;
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
        RolePermissionSyncJob::dispatchSync(true);
    }
}
