<?php

use App\Interfaces\InterfaceClass;
use App\Models\Passport\Client;
use App\Models\Role;
use App\Models\User;
use App\Notifications\TestNotification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

describe('API v1 Routes', function () {
    it('sanitizes input via XSS middleware', function () {
        $payload = ['test' => '<script>alert(1)</script>'];
        $response = $this->postJson(route('app-const'), $payload);
        $response->assertStatus(200);
        $this->assertStringNotContainsString('<script>', json_encode($response->json()));
    });

    it('returns app constants successfully', function () {
        $response = $this->postJson(route('app-const'), ['app_version' => '1.0.0', 'device_id' => 'dev1', 'device_platform' => 'android']);
        $response->assertStatus(200);
    });
    it('validates post-app-const payload', function () {
        $response = $this->postJson(route('app-const'), []);
        $response->assertStatus(200);
    });

    it('logs agent successfully', function () {
        $response = $this->postJson(route('log-agent'), ['message' => 'test log', 'device_id' => 'dev1']);
        $response->assertStatus(200);
    });
    it('validates post-log-agent payload', function () {
        $response = $this->postJson(route('log-agent'), []);
        $response->assertStatus(200);
    });

    it('gets current app version', function () {
        $response = $this->postJson(route('post-get-current-app-version'), [
            'app_version' => '1.0.0',
            'device_id' => 'dev1',
            'device_platform' => 'android',
        ]);
        $response->assertStatus(200);
    });
    it('validates post-get-current-app-version payload', function () {
        $response = $this->postJson(route('post-get-current-app-version'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['app_version', 'device_id', 'device_platform']);
    });

    it('requires auth for get-notification-list', function () {
        $response = $this->postJson(route('get-notification-list'), []);
        $response->assertStatus(200);
    });
    it('gets notification list as authenticated user', function () {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $response = $this->postJson(route('get-notification-list'), []);
        $response->assertStatus(200);
    });

    it('returns token with valid credentials', function () {
        Config::set('challenge.bypass', true);
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        $response = $this->postJson(route('post-token'), [
            'username' => 'testuser',
            'password' => 'password',
            'device_id' => 'dev1',
            'device_name' => 'Test Device',
            'device_model' => 'Model X',
            'device_platform' => 'android',
            'token' => 'validtoken',
        ]);
        $response->assertStatus(200);
    });
    it('fails login with invalid credentials', function () {
        Config::set('challenge.bypass', true);
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        $response = $this->postJson(route('post-token'), [
            'username' => 'wronguser',
            'password' => 'wrong',
            'device_id' => 'dev1',
            'device_name' => 'Test Device',
            'device_model' => 'Model X',
            'device_platform' => 'android',
            'token' => 'validtoken',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username']);
    });
    it('validates post-token payload', function () {
        $response = $this->postJson(route('post-token'), []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['username', 'password', 'device_id', 'device_name', 'device_model', 'device_platform', 'token']);
    });

    describe('Authenticated API v1', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            $this->actingAs($this->user, 'api');
        });

        it('revokes token', function () {
            Config::set('challenge.bypass', true);
            $user = User::factory()->createOne([
                'username' => 'testuser',
                'password' => Hash::make('password'),
            ]);
            // Call the real API to get a valid token
            $loginResponse = $this->postJson(route('post-token'), [
                'username' => 'testuser',
                'password' => 'password',
                'device_id' => 'dev1',
                'device_name' => 'Test Device',
                'device_model' => 'Model X',
                'device_platform' => 'android',
                'token' => 'validtoken',
            ]);
            $loginResponse->assertStatus(200);
            $accessToken = $loginResponse->json('access_token');
            $this->withHeader('Authorization', 'Bearer '.$accessToken);
            $response = $this->postJson(route('post-token-revoke'), []);
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'status',
                'title',
                'message',
            ]);
        });
        it('marks notification as read', function () {
            $user = $this->user;
            $user->notify(new TestNotification);
            $notification = $user->notifications()->latest()->first();
            $response = $this->postJson(route('post-notification-as-read'), ['notification_id' => $notification->id]);
            $response->assertStatus(200);
        });
        it('clears all notifications', function () {
            $response = $this->postJson(route('post-notification-clear-all'), []);
            $response->assertStatus(200);
        });
        it('updates profile', function () {
            $response = $this->postJson(route('post-update-profile'), ['name' => 'New Name']);
            $response->assertStatus(200);
        });
        it('validates update profile', function () {
            $response = $this->postJson(route('post-update-profile'), ['name' => '']);
            $response->assertStatus(422);
        });
    });

    describe('Super Permission API v1', function () {
        beforeEach(function () {
            $this->user = User::factory()->create();
            $this->user->syncRoles([InterfaceClass::SUPERROLE]);
            $this->actingAs($this->user, 'api');
            $this->client = Client::create([
                'id' => Str::uuid(),
                'name' => 'Test Client',
                'secret' => Str::random(40),
                'provider' => null,
                'redirect_uris' => ['http://localhost'],
                'grant_types' => ['password'],
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        it('gets user list', function () {
            $response = $this->postJson(route('get-user-list'), []);
            $response->assertStatus(200);
        });
        it('gets user role perm', function () {
            $response = $this->postJson(route('get-user-role-perm'), ['user_id' => $this->user->id]);
            $response->assertStatus(200);
        });
        it('submits user management', function () {
            $response = $this->postJson(route('post-user-man-submit'), [
                'type_create' => true,
                'username' => 'newuser',
                'name' => 'User',
                'email' => 'user@example.com',
            ]);
            $response->assertStatus(200);
        });
        it('deletes user management', function () {
            $response = $this->postJson(route('post-delete-user-man-submit'), ['id' => $this->user->id]);
            $response->assertStatus(200);
        });
        it('resets user password', function () {
            $response = $this->postJson(route('post-reset-password-user-man-submit'), ['id' => $this->user->id]);
            $response->assertStatus(200);
        });
        it('gets role list', function () {
            $response = $this->postJson(route('get-role-list'), []);
            $response->assertStatus(200);
        });
        it('submits role', function () {
            $response = $this->postJson(route('post-role-submit'), [
                'type_create' => true,
                'role_name' => 'role',
                'permissions' => ['perm1'],
            ]);
            $response->assertStatus(200);
        });
        it('deletes role', function () {
            $role = Role::factory()->create();
            $response = $this->postJson(route('post-delete-role-submit'), ['id' => $role->id]);
            $response->assertStatus(200);
        });
        it('gets server logs', function () {
            $response = $this->postJson(route('get-server-logs'), []);
            $response->assertStatus(200);
        });
        it('clears app cache', function () {
            $response = $this->postJson(route('post-clear-app-cache'), []);
            $response->assertStatus(200);
        });
        it('lists passport clients', function () {
            $response = $this->postJson(route('passport.clients.index'), []);
            $response->assertStatus(200);
        });
        it('resets passport client secret', function () {
            $response = $this->postJson(route('passport.clients.reset-secret'), ['id' => $this->client->id]);
            $response->assertStatus(200);
        });
        it('deletes passport client', function () {
            $response = $this->postJson(route('passport.clients.destroy'), ['id' => $this->client->id]);
            $response->assertStatus(200);
        });
        it('updates passport client', function () {
            $response = $this->postJson(route('passport.clients.update'), ['id' => $this->client->id, 'name' => 'Updated Client']);
            $response->assertStatus(200);
        });
        it('creates passport client', function () {
            $response = $this->postJson(route('passport.clients.store'), [
                'name' => 'New Client',
                'grant_types' => ['password'],
            ]);
            $response->assertStatus(200);
        });
    });
});
