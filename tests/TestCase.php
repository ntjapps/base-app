<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    /**
     * The test seed.
     */
    protected function testSeed(): array
    {
        $this->withoutMiddleware([
            ThrottleRequestsWithRedis::class,
        ]);

        return [
            \Database\Seeders\RolesPermissionSeeder::class,
        ];
    }

    /**
     * Common API test.
     */
    protected function CommonPreparePat(): void
    {
        Artisan::call('passport:client:env');
    }
}
