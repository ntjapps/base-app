<?php

namespace Tests\Feature;

use App\Interfaces\InterfaceClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Gate;
use Tests\AuthTestCase;
use Tests\TestCase;

class BasicPolicyTest extends AuthTestCase
{
    /**
     * Test base policy trait
     */
    public function test_base_policy_trait(): void
    {
        $user = $this->commonSeedTestData();
        
        $this->assertTrue(Gate::forUser($user)->allows('allowAllAction', User::class));
    }

    /**
     * Test super permission
     */
    public function test_super_permission(): void
    {
        $user = $this->commonSeedTestData();
        $user->syncPermissions(InterfaceClass::SUPER);

        $this->assertTrue(Gate::forUser($user)->allows('hasSuperPermission', User::class));

        $user->syncPermissions([]);
        $user->syncRoles(InterfaceClass::SUPERROLE);

        $this->assertTrue(Gate::forUser($user)->allows('hasSuperPermission', User::class));
    }
}
