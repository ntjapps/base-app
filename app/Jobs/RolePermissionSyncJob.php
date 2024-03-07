<?php

namespace App\Jobs;

use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use App\Models\Role;
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
    public function __construct(public bool $reset = false)
    {
        //$this->onQueue('default');
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    //public $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'RolePermissionSyncJob';
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 60;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['RolePermissionSyncJob', 'uniqueId: '.$this->uniqueId()];
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
            Permission::firstOrCreate(['name' => InterfaceClass::SUPER]);

            /** Create roles and assign created permissions */
            $super = Role::firstOrCreate(['name' => InterfaceClass::SUPERROLE]);
            if ($this->reset) {
                $super->syncPermissions([InterfaceClass::SUPER]);
            } else {
                if ($super->hasAnyPermission(InterfaceClass::SUPER)) {
                    $super->givePermissionTo(InterfaceClass::SUPER);
                }
            }

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
