<?php

namespace Database\Seeders;

use App\Models\AgentAvailability;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates agent availability records for users with appropriate roles.
     * You can customize which users should be agents by modifying the query.
     */
    public function run(): void
    {
        $now = now();

        $roleNames = ['agent', 'admin', 'super-admin'];

        User::whereHas('roles', function ($q) use ($roleNames) {
            $q->whereIn('name', $roleNames);
        })
            ->select('id')
            ->distinct()
            ->chunk(100, function ($users) use ($now) {
                foreach ($users as $user) {
                    AgentAvailability::create([
                        'id' => Str::uuid()->toString(),
                        'user_id' => $user->id,
                        'status' => 'offline',
                        'max_load' => 5,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });

        $count = AgentAvailability::count();
        if ($count) {
            $this->command->info('✅ Created '.$count.' agent availability records');
        } else {
            $this->command->warn('⚠️  No users with agent/admin roles found. No agent availability records created.');
            $this->command->info('💡 You can manually insert records or adjust the role filter in AgentAvailabilitySeeder.');
        }
    }
}
