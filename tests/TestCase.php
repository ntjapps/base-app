<?php

namespace Tests;

use App\Jobs\RolePermissionSyncJob;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

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
        if (! Role::exists()) {
            RolePermissionSyncJob::dispatchSync(true);
        }

        // Setup passport client
        $this->artisan('passport:client:env');
        $this->artisan('passport:client:rabbitmq:env');
    }
}
