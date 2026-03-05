<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    config(['challenge.bypass' => true]);
});

test('post login succeeds with valid credentials', function () {
    $user = User::factory()->create([
        'username' => 'alice',
    ]);

    $response = $this->postJson('/post-login', [
        'username' => 'alice',
        'password' => 'password',
        'token' => 't',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'success');
    expect(Auth::check())->toBeTrue();
    expect(Auth::id())->toBe($user->id);
});

test('post login returns validation error with wrong password', function () {
    User::factory()->create([
        'username' => 'bob',
    ]);

    $response = $this->postJson('/post-login', [
        'username' => 'bob',
        'password' => 'wrong',
        'token' => 't',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('errors.username.0', 'Username or password is incorrect');
});
