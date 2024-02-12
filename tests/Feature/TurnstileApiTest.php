<?php

namespace Tests\Feature;

use App\Traits\Turnstile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileApiTest extends TestCase
{
    use Turnstile;

    /**
     * Test verify challenge method.
     */
    public function test_verify_challenge_method(): void
    {
        Http::fake([
            config('challenge.url') => Http::response([
                'success' => true,
            ]),
        ]);

        $this->assertTrue($this->verifyChallenge('test'));
    }

    /**
     * Test verify mobile challenge method.
     */
    public function test_verify_mobile_challenge_method(): void
    {
        Http::fake([
            config('challenge.url') => Http::response([
                'success' => true,
            ]),
        ]);

        $this->assertTrue($this->verifyMobileChallenge('test'));
    }
}
