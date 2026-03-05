<?php

use App\Interfaces\RoleConstants;
use App\Models\User;

describe('Super Admin Pages', function () {
    it('allows super admin to open additional admin pages', function () {
        $user = User::factory()->createOne();
        $user->syncRoles([RoleConstants::SUPER_ADMIN]);
        $this->actingAs($user);

        $this->get(route('division-man'))->assertStatus(200);
        $this->get(route('tag-man'))->assertStatus(200);
        $this->get(route('ai-model-instruction-man'))->assertStatus(200);
    });
});
