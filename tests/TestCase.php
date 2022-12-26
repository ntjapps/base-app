<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * The test seed.
     */
    protected function testSeed(): array
    {
        return [
            \Database\Seeders\RolesPermissionSeeder::class,
        ];
    }
}
