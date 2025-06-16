<?php

use App\Models\User;
use App\Interfaces\InterfaceClass;

$protectedRoutes = [
    'get-logout', 'post-logout', 'profile', 'dashboard', 'user-man', 'role-man', 'server-logs', 'passport-man',
];
$superRoutes = ['user-man', 'role-man', 'server-logs', 'passport-man'];

it('loads landing page for guest', fn() => $this->get('/')->assertStatus(200));
it('sanctum csrf cookie returns token', fn() => $this->get('/sanctum/csrf-cookie')->assertStatus(200)->assertJsonStructure(['status', 'csrf_token']));
it('php ip detect returns success in local env', function () {
    $this->app->detectEnvironment(fn () => 'local');
    $this->get('/php-ip-detect')->assertStatus(200)->assertJsonStructure(['status', 'ip']);
});
it('php ip detect returns error in non-local env', function () {
    $this->app->detectEnvironment(fn () => 'production');
    $this->get('/php-ip-detect')->assertStatus(403)->assertJson(['status' => 'error']);
});
it('login redirect route redirects to landing page', fn() => $this->get(route('login'))->assertRedirect(route('landing-page')));
it('landing page loads for guest', fn() => $this->get(route('landing-page'))->assertStatus(200));
it('post login route returns redirect or validation error', fn() => expect(in_array($this->post(route('post-login'), [])->status(), [302, 422]))->toBeTrue());

it('redirects authenticated user from guest routes', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $this->get(route('landing-page'))->assertRedirect(route('dashboard'));
    expect(in_array($this->post(route('post-login'), [])->status(), [302, 200, 422]))->toBeTrue();
});

it('redirects guest from protected routes', function () use ($protectedRoutes) {
    foreach ($protectedRoutes as $routeName) {
        $method = $routeName === 'post-logout' ? 'post' : 'get';
        $this->{$method}(route($routeName))->assertRedirect(route('login'));
    }
});
it('allows authenticated user to get-logout', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $this->get(route('get-logout'))->assertRedirect(route('landing-page'));
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

it('forbids normal user from super routes', function () use ($superRoutes) {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    foreach ($superRoutes as $routeName) {
        $this->get(route($routeName))->assertForbidden();
    }
});
it('allows super user to super routes', function () use ($superRoutes) {
    $user = User::factory()->createOne();
    $user->syncRoles([InterfaceClass::SUPERROLE]);
    $this->actingAs($user);
    foreach ($superRoutes as $routeName) {
        $this->get(route($routeName))->assertStatus(200);
    }
});

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

it('post logout as authenticated user returns json', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $this->post(route('post-logout'))->assertJson(['status' => 'success'])->assertJsonStructure(['status', 'title', 'message']);
});
it('get logout as authenticated user redirects to landing page', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $this->get(route('get-logout'))->assertRedirect(route('landing-page'));
});
