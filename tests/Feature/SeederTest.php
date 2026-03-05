<?php

namespace Tests\Feature;

use App\Interfaces\PermissionConstants;
use App\Interfaces\RoleConstants;
use App\Models\AgentAvailability;
use App\Models\AgentRoutingRule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AgentAvailabilitySeeder;
use Database\Seeders\AgentRoutingRuleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_creates_roles_and_permissions(): void
    {
        $this->artisan('db:seed', ['--class' => PermissionSeeder::class])->assertExitCode(0);

        $this->assertDatabaseHas('roles', ['name' => RoleConstants::SUPER_ADMIN]);
        $this->assertDatabaseHas('permissions', ['name' => PermissionConstants::SUPER_ADMIN]);

        // Basic counts - there should be at least the number of roles declared
        $this->assertGreaterThanOrEqual(count(RoleConstants::all()), Role::count());
        $this->assertGreaterThanOrEqual(count(PermissionConstants::all()), Permission::count());
    }

    public function test_agent_seeders_create_availability_and_routing_rules(): void
    {
        // Seed roles/permissions first (Agent seeders depend on roles existing)
        $this->artisan('db:seed', ['--class' => PermissionSeeder::class])->assertExitCode(0);

        // Create a user and assign a role that should qualify them as an agent
        $user = User::factory()->create();
        $user->syncRoles([RoleConstants::ADMIN]);

        // Run the availability seeder
        $this->artisan('db:seed', ['--class' => AgentAvailabilitySeeder::class])->assertExitCode(0);

        $this->assertDatabaseHas('agent_availability', ['user_id' => $user->id]);

        // Run the routing rule seeder
        $this->artisan('db:seed', ['--class' => AgentRoutingRuleSeeder::class])->assertExitCode(0);

        $this->assertDatabaseHas('agent_routing_rules', ['user_id' => $user->id]);

        // Basic model checks
        $this->assertGreaterThanOrEqual(1, AgentAvailability::count());
        $this->assertGreaterThanOrEqual(1, AgentRoutingRule::count());
    }
}
