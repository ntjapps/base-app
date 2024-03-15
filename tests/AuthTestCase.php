<?php

namespace Tests;

use App\Models\User;
use Laravel\Passport\Passport;

abstract class AuthTestCase extends TestCase
{
    /**
     * Common seed data for all tests.
     */
    protected function commonSeedTestData(): User
    {
        $this->seed($this->testSeed());

        $user = User::factory()->create();

        Passport::actingAs($user);

        return $user;
    }
}
