<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class MatrixRoleRevokePermCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:revoke {role} {permission}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke permission for given role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Role::find(Role::where('name', $this->argument('role'))->first()->id)->revokePermissionTo($this->argument('permission'));

        /** Reset cached roles and permissions */
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Feature::flushCache();
        Log::alert('Console role:revoke executed', ['role' => $this->argument('role'), 'permission' => $this->argument('permission')]);
    }
}
