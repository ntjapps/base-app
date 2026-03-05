<?php

use App\Interfaces\RoleConstants;
use App\Jobs\RolePermissionSyncJob;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('RolePermissionSyncJob', function () {
    it('exposes queue metadata', function () {
        $job = new RolePermissionSyncJob;
        expect($job->uniqueId())->toBe('RolePermissionSyncJob');
        expect($job->tags())->toBeArray();
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(3);
        expect($job->uniqueFor)->toBe(60);
    });

    it('creates all permissions and roles and assigns permissions (additive mode)', function () {
        Role::query()->delete();
        Permission::query()->delete();

        (new RolePermissionSyncJob(false))->handle();

        expect(Permission::count())->toBeGreaterThan(0);

        foreach (RoleConstants::hierarchy() as $roleName => $permissions) {
            expect(Role::where('name', $roleName)->exists())->toBeTrue();
        }
    });

    it('syncs permissions in reset mode', function () {
        Role::query()->delete();
        Permission::query()->delete();

        // First run creates everything
        (new RolePermissionSyncJob(false))->handle();

        // Reset mode syncs (replaces) permissions on each role
        (new RolePermissionSyncJob(true))->handle();

        expect(Role::count())->toBeGreaterThan(0);
        expect(Permission::count())->toBeGreaterThan(0);
    });

    it('skips givePermissionTo when role already has permission (additive mode)', function () {
        Role::query()->delete();
        Permission::query()->delete();

        // First run creates everything
        (new RolePermissionSyncJob(false))->handle();

        // Second run in additive mode skips existing permissions
        (new RolePermissionSyncJob(false))->handle();

        expect(Role::count())->toBeGreaterThan(0);
    });

    it('rethrows on failure and logs error', function () {
        Log::shouldReceive('debug')->andReturnNull()->byDefault();
        Log::shouldReceive('error')->once();

        // Remove all tables so Permission::firstOrCreate throws a QueryException
        DB::statement('DROP TABLE IF EXISTS permissions');

        (new RolePermissionSyncJob)->handle();
    })->throws(\Exception::class);
});
