<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test open the login redirect
     */
    public function test_open_login_redirect(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(302)->assertRedirectToRoute('landing-page');
    }

    /**
     * Test open the home
     */
    public function test_open_home(): void
    {
        $response = $this->get(route('landing-page'));

        $response->assertStatus(200)->assertViewIs('auth-pg.login');
    }

    /**
     * Test the post login form.
     */
    public function test_post_login_form(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson(route('post-login'), [
            'username' => $user->username,
            'password' => 'password',
            'token' => 'token',
        ]);

        $response->assertStatus(200)->assertJsonIsObject();
    }

    /**
     * Test the post logout form.
     */
    public function test_post_logout_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('post-logout'));

        $response->assertStatus(200)->assertJsonIsObject();
    }

    /**
     * Test the get logout form.
     */
    public function test_get_logout_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('get-logout'));

        $response->assertStatus(302)->assertRedirectToRoute('landing-page');
    }

    /**
     * Test open the profile
     */
    public function test_open_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertStatus(200)->assertViewIs('dash-pg.profile');
    }

    /**
     * Test open dashboard
     */
    public function test_open_dashboard(): void
    {
        $this->seed($this->testSeed());

        $user = User::factory()->create()->givePermissionTo(User::SUPER);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200)->assertViewIs('dash-pg.dashboard');
    }

    /**
     * Test open user management
     */
    public function test_open_user_man(): void
    {
        $this->seed($this->testSeed());

        $user = User::factory()->create()->givePermissionTo(User::SUPER);

        $response = $this->actingAs($user)->get(route('user-man'));

        $response->assertStatus(200)->assertViewIs('super-pg.userman');
    }

    /**
     * Test open server logs
     */
    public function test_open_server_logs(): void
    {
        $this->seed($this->testSeed());

        $user = User::factory()->create()->givePermissionTo(User::SUPER);

        $response = $this->actingAs($user)->get(route('server-logs'));

        $response->assertStatus(200)->assertViewIs('super-pg.serverlog');
    }
}
