<?php

use App\Models\User;
use App\Policies\BasePolicy;

class BasePolicyHarness
{
    use BasePolicy;

    public function allow(User $user): bool
    {
        return $this->allowAllAction($user);
    }

    public function deny(): bool
    {
        return $this->denyAllAction();
    }
}

describe('BasePolicy', function () {
    it('allows or denies actions', function () {
        $user = User::factory()->create();
        $h = new BasePolicyHarness;

        expect($h->allow($user))->toBeTrue();
        expect($h->deny())->toBeFalse();
    });
});
