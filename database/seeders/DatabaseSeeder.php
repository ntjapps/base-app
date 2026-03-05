<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Interfaces\InterfaceClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        /** Flush All Cache */
        Cache::flush();
        InterfaceClass::flushRolePermissionCache();

        $this->call([
            PassportInitSeeder::class,
            PermissionSeeder::class,
            SystemTagSeeder::class,
            DivisionSeeder::class,
            LocalUserSeeder::class,
            AgentAvailabilitySeeder::class,
            AgentRoutingRuleSeeder::class,
        ]);
    }
}
