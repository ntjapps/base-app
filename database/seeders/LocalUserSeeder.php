<?php

namespace Database\Seeders;

use App\Interfaces\RoleConstants;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

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

        // If there are any active users (not soft-deleted), do not create a local admin
        if (User::whereNull('deleted_at')->exists()) {
            $this->command->info('Active user found, skipping local admin seeding.');

            return;
        }

        // Use firstOrCreate to ensure seeder is idempotent
        $user = User::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles([RoleConstants::SUPER_ADMIN]);
    }
}
