<?php

use App\Rules\TokenPlatformValidation;
use App\Rules\TurnstileValidation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('Validation rules', function () {
    it('validates token platform', function () {
        $r = new TokenPlatformValidation;
        $failed = 0;
        $fail = function () use (&$failed) {
            $failed++;
        };

        $r->validate('platform', 'web', $fail);
        $r->validate('platform', 'bad', $fail);

        expect($failed)->toBe(1);
    });

    it('validates turnstile with bypass and remote verification', function () {
        $r = new TurnstileValidation;
        $failed = 0;
        $fail = function () use (&$failed) {
            $failed++;
        };

        Config::set('challenge.bypass', true);
        $r->validate('t', 'x', $fail);
        expect($failed)->toBe(0);

        Config::set('challenge.bypass', false);
        Config::set('challenge.url', 'https://turnstile.test/verify');
        Config::set('challenge.site_secret', 's1');

        Http::fake([
            'https://turnstile.test/verify' => Http::sequence()
                ->push(['success' => false], 200)
                ->push(['success' => true], 200),
        ]);

        $r->validate('t', 'x', $fail);
        $r->validate('t', 'x', $fail);
        expect($failed)->toBe(1);
    });
});
