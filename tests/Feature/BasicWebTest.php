<?php

namespace Tests\Feature;

use Tests\TestCase;

class BasicWebTest extends TestCase
{
    /**
     * Constant test sanctum/csrf-cookie
     */
    public function test_constant_sanctum_csrf_cookie(): void
    {
        $this->get('/sanctum/csrf-cookie')
            ->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }

    /**
     * Constant test app/healthcheck
     */
    public function test_constant_app_healthcheck(): void
    {
        $this->get('/app/healthcheck')
            ->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);
    }

    /**
     * Constant test login-redirect
     */
    public function test_constant_login_redirect(): void
    {
        $this->get('/login-redirect')
            ->assertRedirect(route('landing-page'));
    }
}
