<?php

namespace Database\Seeders;

use App\Models\PassportClient;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportInitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PassportClient::where('name', 'Personal Access Client Env')->delete();

        $client = new ClientRepository();
        $client->createPersonalAccessClient(null, 'Personal Access Client Env', 'http://localhost');

        $dbClient = PassportClient::where('name', 'Personal Access Client Env')->first();
        $dbClient->id = config('passport.personal_access_client.id');
        $dbClient->secret = config('passport.personal_access_client.secret');
        $dbClient->save();
    }
}
