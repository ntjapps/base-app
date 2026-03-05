<?php

use App\Interfaces\RoleConstants;
use App\Models\User;

$protectedRoutes = [
    'get-logout',
    'post-logout',
    'profile',
    'dashboard',
    'user-man',
    'role-man',
    'route-analytics',
    'server-logs',
    'passport-man',
];
$superRoutes = ['user-man', 'role-man', 'route-analytics', 'server-logs', 'passport-man'];

describe('Public and Guest Routes', function () {
    it('sanctum csrf cookie returns token', fn () => $this->get('/sanctum/csrf-cookie')->assertStatus(200)->assertJsonStructure(['status', 'csrf_token']));
    it('php ip detect returns success in local env', function () {
        $this->app->detectEnvironment(fn () => 'local');
        $this->get('/php-ip-detect')->assertStatus(200)->assertJsonStructure(['status', 'ip']);
    });
    it('php ip detect returns error in non-local env', function () {
        $this->app->detectEnvironment(fn () => 'production');
        $this->get('/php-ip-detect')->assertStatus(403)->assertJson(['status' => 'error']);
    });
    it('login redirect route redirects to login page', fn () => $this->get(route('login'))->assertRedirect(route('login-page')));
    it('post login route returns redirect or validation error', fn () => expect(in_array($this->post(route('post-login'), [])->status(), [302, 422]))->toBeTrue());
});

describe('Auth Middleware', function () use ($protectedRoutes) {
    it('redirects guest from protected routes', function () use ($protectedRoutes) {
        foreach ($protectedRoutes as $routeName) {
            $method = $routeName === 'post-logout' ? 'post' : 'get';
            $this->{$method}(route($routeName))->assertRedirect(route('login'));
        }
    });
    it('allows authenticated user to get-logout', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('get-logout'))->assertStatus(200);
    });
    it('allows authenticated user to post-logout', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->post(route('post-logout'))->assertJson(['status' => 'success']);
    });
    it('allows authenticated user to profile', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('profile'))->assertStatus(200);
    });
    it('allows authenticated user to dashboard', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertStatus(200);
    });
});

describe('ProfileFillIfEmpty Middleware', function () {
    it('redirects to profile if dashboard user name is empty', function () {
        $user = User::factory()->createOne(['name' => null]);
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertRedirect(route('profile'));
    });
    it('dashboard as authenticated user with name does not redirect to profile', function () {
        $user = User::factory()->createOne(['name' => 'Test User']);
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertStatus(200)->assertViewIs('base-components.base');
    });
});

describe('can:hasSuperPermission Middleware', function () use ($superRoutes) {
    it('forbids normal user from super routes', function () use ($superRoutes) {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        foreach ($superRoutes as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    });
    it('allows super user to super routes', function () use ($superRoutes) {
        $user = User::factory()->createOne();
        $user->syncRoles([RoleConstants::SUPER_ADMIN]);
        $this->actingAs($user);
        foreach ($superRoutes as $routeName) {
            $this->get(route($routeName))->assertStatus(200);
        }
    });
});

describe('Base View Assertions', function () {
    it('profile as authenticated user returns base view', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('profile'))->assertStatus(200)->assertViewIs('base-components.base');
    });
    it('dashboard as authenticated user returns base view', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('dashboard'))->assertStatus(200)->assertViewIs('base-components.base');
    });
});

describe('Logout and JSON Structure', function () {
    it('post logout as authenticated user returns json', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->post(route('post-logout'))->assertJson(['status' => 'success'])->assertJsonStructure(['status', 'title', 'message']);
    });
    it('get logout as authenticated user returns successful', function () {
        $user = User::factory()->createOne();
        $this->actingAs($user);
        $this->get(route('get-logout'))->assertStatus(200);
    });
});
