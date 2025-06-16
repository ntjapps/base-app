<?php

use App\Models\User;

it('allows authenticated user to their own private channel', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "App.Models.User.{$user->id}",
        'socket_id' => '1234.5678',
    ]);
    $response->assertStatus(200);
});

it('allows authenticated user to all channel', function () {
    $user = User::factory()->createOne();
    $this->actingAs($user);
    $response = $this->postJson('/broadcasting/auth', [
        'channel_name' => "all",
        'socket_id' => '1234.5678',
    ]);
    $response->assertStatus(200);
});
