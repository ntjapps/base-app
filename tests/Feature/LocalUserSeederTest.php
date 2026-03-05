<?php

use App\Models\User;

describe('LocalUserSeeder', function () {
    it('creates admin user when there are no active users', function () {
        // Ensure no users exist
        expect(User::count())->toBe(0);

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LocalUserSeeder']);

        $user = User::where('username', 'admin')->first();
        expect($user)->not->toBeNull();
        expect($user->deleted_at)->toBeNull();
    });

    it('does not create admin when there is an active user', function () {
        // Create an active user
        User::factory()->create();

        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\LocalUserSeeder']);

        $usersCount = User::count();
        expect($usersCount)->toBe(1);
    });
});
