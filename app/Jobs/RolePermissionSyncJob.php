<?php

namespace App\Jobs;

use App\Interfaces\PermissionConstants;
use App\Interfaces\RoleConstants;
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
        // $this->onQueue('default');
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // public $timeout = 60;

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

            /** Create all defined permissions */
            collect(PermissionConstants::all())->each(function ($permName) {
                Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => Permission::GUARD_NAME,
                ]);
            });

            /** Create all defined roles and assign their permissions */
            foreach (RoleConstants::hierarchy() as $roleName => $permissions) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => Permission::GUARD_NAME,
                ]);

                if ($this->reset) {
                    $role->syncPermissions($permissions);
                } else {
                    // Only add missing permissions, don't remove existing ones
                    foreach ($permissions as $permName) {
                        if (! $role->hasPermissionTo($permName)) {
                            $role->givePermissionTo($permName);
                        }
                    }
                }
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            Log::debug('Job Finished', ['jobName' => 'RolePermissionSyncJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'RolePermissionSyncJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
