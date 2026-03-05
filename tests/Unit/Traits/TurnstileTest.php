<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class TurnstileHarness
{
    use App\Traits\Turnstile;

    public function verify(string $value): bool
    {
        return $this->verifyChallenge($value);
    }

    public function verifyMobile(string $value): bool
    {
        return $this->verifyMobileChallenge($value);
    }
}

describe('Turnstile', function () {
    it('verifies challenges via HTTP', function () {
        Config::set('challenge.url', 'https://turnstile.test/verify');
        Config::set('challenge.site_secret', 's1');
        Config::set('challenge.site_secret_mobile', 's2');

        Http::fake([
            'https://turnstile.test/verify' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['success' => false], 200),
        ]);

        $h = new TurnstileHarness;
        expect($h->verify('t'))->toBeTrue();
        expect($h->verifyMobile('t'))->toBeFalse();
    });
});
