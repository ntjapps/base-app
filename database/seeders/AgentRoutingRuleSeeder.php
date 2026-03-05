<?php

namespace Database\Seeders;

use App\Models\AgentAvailability;
use App\Models\AgentRoutingRule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentRoutingRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates routing rules for agents based on their division assignments.
     * This is optional - if no routing rules exist, agents can handle all divisions.
     */
    public function run(): void
    {
        $availabilities = AgentAvailability::with('user')->get();

        $now = now();
        $count = 0;

        foreach ($availabilities as $avail) {
            AgentRoutingRule::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $avail->user_id,
                'division' => optional($avail->user)->division,
                'priority' => 10,
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $count++;
        }

        if ($count) {
            $this->command->info('✅ Created '.$count.' agent routing rules');
        } else {
            $this->command->warn('⚠️  No agent availability records found. Run AgentAvailabilitySeeder first.');
        }

        // Example: Add custom high-priority rule for a specific senior agent
        $seniorAgentEmail = 'senior.agent@example.com';
        $seniorAgent = User::where('email', $seniorAgentEmail)->first();

        if ($seniorAgent) {
            AgentRoutingRule::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $seniorAgent->id,
                'division' => null, // Can handle all divisions
                'priority' => 100, // Very high priority
                'enabled' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->command->info("✅ Created high-priority rule for senior agent: {$seniorAgentEmail}");
        }
    }
}
