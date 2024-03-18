<?php

namespace Tests;

use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

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
        Config::set('passport.personal_access_client.id', (string) Str::orderedUuid());
        Artisan::call('passport:keys');
        Artisan::call('passport:client:env');
    }
}
