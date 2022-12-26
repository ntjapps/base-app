<?php

namespace App\Traits;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

trait CryptFunction
{
    /**
     * Encrypt function wrapper
     */
    protected function encrypt(string $data): string
    {
        return Crypt::encryptString($data);
    }

    /**
     * Decrypt function wrapper
     */
    protected function decrypt(string $data): mixed
    {
        try {
            return Crypt::decryptString($data);
        } catch (DecryptException $e) {
            Log::error('Failed to decrypt data', ['data' => $data, 'error' => $e->getMessage()]);
        }
    }
}
