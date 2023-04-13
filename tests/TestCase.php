<?php

namespace Tests;

use App\Models\PassportClient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Passport\ClientRepository;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * The test seed.
     */
    protected function testSeed(): array
    {
        Permission::truncate();
        Role::truncate();
        User::truncate();

        return [
            \Database\Seeders\RolesPermissionSeeder::class,
        ];
    }

    /**
     * Common API test.
     */
    protected function CommonPreparePat(): void
    {
        PassportClient::truncate();

        $client = new ClientRepository();
        $client->createPersonalAccessClient(null, 'Test Client', 'http://localhost');

        $dbClient = PassportClient::where('name', 'Test Client')->first();
        $dbClient->id = env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID');
        $dbClient->secret = env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET');
        $dbClient->save();
    }
}
