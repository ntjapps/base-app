<?php

namespace Tests;

use App\Jobs\RolePermissionSyncJob;
use App\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations
        $this->artisan('migrate', [
            '--force' => true,
            '--no-interaction' => true,
        ]);

        if (method_exists($this, 'withoutVite')) {
            $this->withoutVite();
        }

        // Call the Role Seeder if Role model does not exist
        if (!Role::exists()) {
            RolePermissionSyncJob::dispatchSync(true);
        }
    }
}
