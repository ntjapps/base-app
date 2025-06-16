<?php

test('get landing page', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});

test('sanctum csrf cookie returns token', function () {
    $response = $this->get('/sanctum/csrf-cookie');
    $response->assertStatus(200);
    $response->assertJsonStructure(['status', 'csrf_token']);
});

test('php ip detect returns success in local env', function () {
    $this->app->detectEnvironment(fn () => 'local');
    $response = $this->get('/php-ip-detect');
    $response->assertStatus(200);
    $response->assertJsonStructure(['status', 'ip']);
});

test('php ip detect returns error in non-local env', function () {
    $this->app->detectEnvironment(fn () => 'production');
    $response = $this->get('/php-ip-detect');
    $response->assertStatus(403);
    $response->assertJson(['status' => 'error']);
});

test('login redirect route redirects to landing page', function () {
    $response = $this->get(route('login'));
    $response->assertRedirect(route('landing-page'));
});

test('landing page loads for guest', function () {
    $response = $this->get(route('landing-page'));
    $response->assertStatus(200);
});

test('post login route returns redirect or validation error', function () {
    $response = $this->post(route('post-login'), []);
    expect(in_array($response->status(), [302, 422]))->toBeTrue();
});

test('post logout as authenticated user', function () {
    $user = App\Models\User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->post(route('post-logout'));
    $response->assertJson([
        'status' => 'success',
    ]);
    $response->assertJsonStructure(['status', 'title', 'message']);
});

test('get logout as authenticated user', function () {
    $user = App\Models\User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->get(route('get-logout'));
    $response->assertRedirect(route('landing-page'));
});

test('profile as authenticated user', function () {
    $user = App\Models\User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->get(route('profile'));
    $response->assertStatus(200);
    $response->assertViewIs('base-components.base');
});

test('dashboard as authenticated user', function () {
    $user = App\Models\User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
    $response->assertViewIs('base-components.base');
});

test('get logout requires auth', function () {
    $response = $this->get(route('get-logout'));
    $response->assertRedirect(route('login'));
});

test('post logout requires auth', function () {
    $response = $this->post(route('post-logout'));
    $response->assertRedirect(route('login'));
});

test('profile requires auth', function () {
    $response = $this->get(route('profile'));
    $response->assertRedirect(route('login'));
});

test('dashboard requires auth', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('user man requires auth', function () {
    $response = $this->get(route('user-man'));
    $response->assertRedirect(route('login'));
});

test('role man requires auth', function () {
    $response = $this->get(route('role-man'));
    $response->assertRedirect(route('login'));
});

test('server logs requires auth', function () {
    $response = $this->get(route('server-logs'));
    $response->assertRedirect(route('login'));
});

test('passport man requires auth', function () {
    $response = $this->get(route('passport-man'));
    $response->assertRedirect(route('login'));
});
