<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiTest extends TestCase
{
    /**
     * Test post api app constant.
     */
    public function test_post_api_app_constant(): void
    {
        $this->seed($this->testSeed());

        $response = $this->postJson(route('app-const'));

        $response->assertStatus(200)->assertJson([
            'isAuth' => false,
        ]);
    }

    /**
     * Test post api log agent.
     */
    public function test_post_api_log_agent(): void
    {
        $this->seed($this->testSeed());

        $response = $this->postJson(route('log-agent'));

        $response->assertStatus(200)->assertSee('OK');
    }

    /**
     * Test post api token.
     */
    public function test_post_api_token(): void
    {
        $this->seed($this->testSeed());
        $this->CommonPreparePat();

        $user = User::factory()->create();

        $response = $this->postJson(route('post-token'), [
            'username' => $user->username,
            'password' => 'password',
            'device_id' => Str::uuid(),
            'device_name' => 'Test Device',
            'device_model' => 'Test Model',
            'device_platform' => 'web',
            'token' => 'token',
        ]);

        $response->assertStatus(200)->assertJsonIsObject();
    }

    /**
     * Test post revoke api token.
     */
    public function test_post_revoke_api_token(): void
    {
        $this->seed($this->testSeed());
        $this->CommonPreparePat();

        $user = User::factory()->create();
        $tokenFactory = $user->createToken('Test Device')->accessToken;

        /** Check if Token Exists */
        $this->assertNotNull($user->tokens()->first());

        $response = $this->postJson(route('post-token-revoke'), [], [
            'Authorization' => 'Bearer '.$tokenFactory,
        ]);

        $response->assertStatus(200)->assertJson([
            'message' => 'Token revoked',
        ]);
    }

    /**
     * Test post api update profile.
     */
    public function test_post_update_profile(): void
    {
        $this->seed($this->testSeed());

        Passport::actingAs($user = User::factory()->create());

        $targetName = 'Test Name';

        $response = $this->postJson(route('update-profile'), [
            'name' => $targetName,
        ]);

        $response->assertStatus(200)->assertJson([
            'redirect' => route('dashboard'),
        ]);

        $this->assertEquals($targetName, $user->fresh()->name);
    }

    /**
     * Test post api get user list
     */
    public function test_post_api_get_user_list(): void
    {
        $this->seed($this->testSeed());

        Passport::actingAs($user = User::factory()->create()->givePermissionTo(User::SUPER));

        $response = $this->postJson(route('get-user-list'));

        $response->assertStatus(200)->assertJsonIsArray()->assertSee($user->id);
    }

    /**
     * Test post api get server logs
     */
    public function test_post_api_get_server_logs(): void
    {
        $this->seed($this->testSeed());

        Log::channel('database')->debug('Test Log');

        Passport::actingAs(User::factory()->create()->givePermissionTo(User::SUPER));

        $response = $this->postJson(route('get-server-logs'));

        $response->assertStatus(200)->assertJsonIsArray()->assertSee('Test Log');
    }
}
