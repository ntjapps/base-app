<?php

namespace Tests\Feature;

use App\Interfaces\InterfaceClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteTest extends TestCase
{
    use RefreshDatabase;

    public static function webRoutesProvider(): array
    {
        return [
            ['get', '/', 200, 'base-components.base'],
            ['post', '/post-login', 200, 'base-components.base'],
            ['get', '/login-redirect', 302, null, true],
            ['get', '/sanctum/csrf-cookie', 200, 'base-components.base'],
            ['get', '/php-ip-detect', null],
            ['post', '/post-logout', 302, null, true],
            ['get', '/get-logout', 302, null, true],
            ['get', '/profile', 302, null, true],
            ['get', '/dashboard', 302, null, true],
            ['get', '/user-man', 302, null, true],
            ['get', '/role-man', 302, null, true],
            ['get', '/server-logs', 302, null, true],
            ['get', '/passport-man', 302, null, true],
        ];
    }

    #[DataProvider('webRoutesProvider')]
    public function test_web_routes_test($method, $uri, $expectedStatus, $expectedView = null, $shouldRedirect = false): void
    {
        $this->seed();
        $this->withoutVite();
        if ($uri === '/php-ip-detect') {
            $expectedStatus = app()->environment('local') ? 200 : 403;
        }
        $response = $this->{$method}($uri);
        $response->assertStatus($expectedStatus);
        if ($expectedView) {
            $response->assertViewIs('base-components.base');
        }
        if ($shouldRedirect) {
            $response->assertRedirect();
        }
    }

    public static function apiRoutesProvider(): array
    {
        return [
            ['POST', '/api/v1/post-app-const', 200, ['appName']],
            ['POST', '/api/v1/post-log-agent', 200],
            ['POST', '/api/v1/post-get-current-app-version', 200, ['appUpdate'], false, false, ['app_version' => '1.0.0', 'device_id' => 'test', 'device_platform' => 'android']],
            ['POST', '/api/v1/get-notification-list', 401],
            ['POST', '/api/v1/post-token', 200],
            ['POST', '/api/v1/post-token-revoke', 200, null, true],
            ['POST', '/api/v1/post-notification-as-read', 200, null, true],
            ['POST', '/api/v1/post-notification-clear-all', 200, null, true],
            ['POST', '/api/v1/post-update-profile', 200, null, true],
            ['POST', '/api/v1/get-user-list', 200, null, true, true],
            ['POST', '/api/v1/get-user-role-perm', 200, null, true, true],
            ['POST', '/api/v1/post-user-man-submit', 200, null, true, true],
            ['POST', '/api/v1/post-delete-user-man-submit', 200, null, true, true],
            ['POST', '/api/v1/post-reset-password-user-man-submit', 200, null, true, true],
            ['POST', '/api/v1/get-role-list', 200, null, true, true],
            ['POST', '/api/v1/post-role-submit', 200, null, true, true],
            ['POST', '/api/v1/post-delete-role-submit', 200, null, true, true],
            ['POST', '/api/v1/get-server-logs', 200, null, true, true],
            ['POST', '/api/v1/post-clear-app-cache', 200, null, true, true],
            ['POST', '/api/v1/oauth/post-get-oauth-client', 200, null, true, true],
            ['POST', '/api/v1/oauth/post-reset-oauth-secret', 200, null, true, true],
            ['POST', '/api/v1/oauth/post-delete-oauth-client', 200, null, true, true],
            ['POST', '/api/v1/oauth/post-update-oauth-client', 200, null, true, true],
            ['POST', '/api/v1/oauth/post-create-oauth-client', 200, null, true, true],
            ['POST', '/api/v1/rabbitmq/test-rabbitmq', null],
            ['POST', '/api/v1/rabbitmq/send-notification', 200],
            ['POST', '/api/v1/rabbitmq/send-log', 200],
            ['POST', '/api/v1/rabbitmq/send-callbacks', 200],
        ];
    }

    #[DataProvider('apiRoutesProvider')]
    public function test_api_routes_test($method, $uri, $expectedStatus, $jsonStructure = null, $actingAs = false, $super = false, $payload = []): void
    {
        $this->seed();
        if ($uri === '/api/v1/rabbitmq/test-rabbitmq') {
            $expectedStatus = app()->environment('local') ? 200 : 403;
        }
        $request = $this;
        if ($actingAs) {
            $user = User::factory()->create();
            if ($super) {
                $user->syncRoles([InterfaceClass::SUPERROLE]);
            }
            $request = $request->actingAs($user, 'api');
        }
        $response = $request->json($method, $uri, $payload);
        $response->assertStatus($expectedStatus);
        if ($jsonStructure) {
            $response->assertJsonStructure($jsonStructure);
        }
    }
}
