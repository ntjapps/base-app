<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class LocalDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (App::environment('local')) {
            $user = User::factory()->create([
                'name' => 'Admin',
                'username' => 'admin',
            ]);
            $user->syncRoles([User::SUPERROLE]);
        }
    }
}
