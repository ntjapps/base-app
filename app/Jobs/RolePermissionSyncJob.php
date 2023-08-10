<?php

namespace App\Jobs;

use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RolePermissionSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'RolePermissionSyncJob']);

            /** Reset cached roles and permissions */
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            /** Create permissions */
            Permission::firstOrCreate(['name' => User::SUPER]);

            /** Create roles and assign created permissions */
            $super = Role::firstOrCreate(['name' => User::SUPERROLE]);
            $super->givePermissionTo(User::SUPER);

            /** Update all const permission */
            Permission::whereIn('name', InterfaceClass::ALLPERM)->update(['is_const' => true]);

            /** Update all const role */
            Role::whereIn('name', InterfaceClass::ALLROLE)->update(['is_const' => true]);

            Log::debug('Job Finished', ['jobName' => 'RolePermissionSyncJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'RolePermissionSyncJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
