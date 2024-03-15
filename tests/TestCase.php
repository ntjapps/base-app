<?php

namespace Tests;

use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->withoutMiddleware([
            ThrottleRequestsWithRedis::class,
        ]);
    }

    /**
     * The test seed.
     */
    protected function testSeed(): array
    {
        return [
            RolesPermissionSeeder::class,
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
