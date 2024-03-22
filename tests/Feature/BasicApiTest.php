<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\AuthTestCase;

class BasicApiTest extends AuthTestCase
{
    /**
     * Constant test post-app-const
     */
    public function test_constant_post_app_const(): void
    {
        $this->postJson(route('app-const'))
            ->assertOk()
            ->assertJson([
                'appName' => config('app.name'),
            ]);
    }

    /**
     * Constant test post-log-agent
     */
    public function test_constant_post_log_agent(): void
    {
        $this->postJson(route('log-agent'))
            ->assertOk()
            ->assertSee('OK');
    }

    /**
     * Test app const results
     */
    public function test_app_const_results(): void
    {
        $user = $this->commonSeedTestData();

        $response = $this->actingAs($user)->postJson(route('app-const'));

        $response->assertOk()->assertJsonStructure([
            'appName',
            'userName',
            'isAuth',
        ]);
    }

    /**
     * Test app update version for mobile
     */
    public function test_app_update_version_for_mobile(): void
    {
        $response = $this->postJson(route('post-get-current-app-version'), [
            'app_version' => '1.0.0',
            'device_id' => fake()->uuid(),
            'device_platform' => fake()->randomElement(['android', 'ios']),
        ]);

        $response->assertOk()->assertJsonStructure([
            'appUpdate',
            'appVersion',
            'deviceVersion',
        ])->assertJson([
            'appUpdate' => false,
        ]);

        Config::set('mobile.app_version', '1.0.1');

        $response = $this->postJson(route('post-get-current-app-version'), [
            'app_version' => '1.0.0',
            'device_id' => fake()->uuid(),
            'device_platform' => fake()->randomElement(['android', 'ios']),
        ]);

        $response->assertOk()->assertJsonStructure([
            'appUpdate',
            'appVersion',
            'deviceVersion',
        ])->assertJson([
            'appUpdate' => true,
        ]);
    }
}
