<?php

namespace Tests;

use App\Models\PassportClient;
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
        return [
            \Database\Seeders\RolesPermissionSeeder::class,
        ];
    }

    /**
     * Common API test.
     */
    protected function CommonPreparePat(): void
    {
        $client = new ClientRepository();
        $client->createPersonalAccessClient(null, 'Test Client', 'http://localhost');

        $dbClient = PassportClient::where('name', 'Test Client')->first();
        $this->assertNotNull($dbClient);

        $dbClient->id = env('PASSPORT_PERSONAL_ACCESS_CLIENT_ID');
        $dbClient->secret = env('PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET');
        $dbClient->save();
    }
}
