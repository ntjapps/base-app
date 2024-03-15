<?php

namespace Tests\Feature;

use App\Http\Middleware\ProfileFillIfEmpty;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    /**
     * Test open login password request page.
     */
    public function test_open_login_password_request_page(): void
    {
        $this->seed($this->testSeed());

        $response = $this->get(route('landing-page'));

        $response->assertOk()->assertViewIs('auth-pg.login');
    }

    /**
     * Test submit password for login. This use WEB route.
     */
    public function test_submit_password_for_login(): void
    {
        $this->seed($this->testSeed());

        (string) $password = 'testpassword';

        $user = User::factory()->create();
        $user->password = Hash::make($password);
        $user->save();

        /** Config bypass turnstile */
        Config::set('challenge.bypass', true);

        $this->assertDatabaseHas(User::class, [
            'username' => $user->username,
            'password' => $user->password,
        ]);

        $response = $this->post(route('post-login'), [
            'username' => $user->username,
            'password' => $password,
            'token' => '123456',
        ]);

        $this->withoutMiddleware([
            ProfileFillIfEmpty::class,
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'title' => __('app.login.title'),
            'message' => __('app.login.message'),
            'redirect' => route('dashboard'),
        ]);
    }

    /**
     * Test submit password for login. This use API route.
     */
    public function test_submit_password_for_login_api(): void
    {
        $this->seed($this->testSeed());
        $this->CommonPreparePat();

        (string) $password = 'testpassword';

        $user = User::factory()->create();
        $user->password = Hash::make($password);
        $user->save();

        /** Config bypass turnstile */
        Config::set('challenge.bypass', true);

        $this->assertDatabaseHas(User::class, [
            'username' => $user->username,
            'password' => $user->password,
        ]);

        $response = $this->post(route('post-token'), [
            'username' => $user->username,
            'password' => $password,
            'device_id' => '123456',
            'device_name' => 'Test Device',
            'device_model' => 'Test Model',
            'device_platform' => 'web',
            'token' => '123456',
        ]);

        $response->assertOk()->assertJson([
            'status' => 'success',
            'title' => __('app.token.generated.title'),
            'message' => __('app.token.generated.message'),
        ]);
    }
}
