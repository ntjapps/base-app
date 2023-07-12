<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicApiTest extends TestCase
{
    /**
     * Constant test post-app-const
     */
    public function test_constant_post_app_const(): void
    {
        $this->post(route('app-const'))
            ->assertStatus(200)
            ->assertJson([
                'appName' => config('app.name'),
            ]);
    }

    /**
     * Constant test post-log-agent
     */
    public function test_constant_post_log_agent(): void
    {
        $this->post(route('log-agent'))
            ->assertStatus(200)
            ->assertSee('OK');
    }
}
