<?php

namespace Database\Seeders;

use App\Interfaces\InterfaceClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class LocalUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** Only run in local or testing environment */
        if (! App::environment('local') && ! App::environment('testing')) {
            return;
        }

        $user = User::factory()->create([
            'name' => 'Admin',
            'username' => 'admin',
        ]);
        $user->syncRoles([InterfaceClass::SUPERROLE]);
    }
}
