<?php

namespace Tests\Unit\Traits;

use App\Traits\CryptFunction;

class CryptFunctionHarness
{
    use CryptFunction;

    public function enc(string $value): string
    {
        return $this->encrypt($value);
    }

    public function dec(string $value): mixed
    {
        return $this->decrypt($value);
    }
}

describe('CryptFunction', function () {
    it('encrypts and decrypts strings', function () {
        $h = new CryptFunctionHarness;
        $enc = $h->enc('secret');
        expect($enc)->toBeString();
        expect($h->dec($enc))->toBe('secret');
        expect($h->dec('not-valid'))->toBeNull();
    });
});
