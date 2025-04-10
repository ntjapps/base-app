<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait Turnstile
{
    /**
     * Verify challenge response.
     */
    protected function verifyChallenge(string $value): bool
    {
        $response = Http::asForm()->post(config('challenge.url'), [
            'secret' => config('challenge.site_secret'),
            'response' => $value,
        ]);

        Log::debug('Turnstile response', [
            'response' => $response->json(),
        ], ['trait' => 'Turnstile']);

        return $response->json()['success'];
    }

    /**
     * Verify challenge response.
     */
    protected function verifyMobileChallenge(string $value): bool
    {
        $response = Http::asForm()->post(config('challenge.url'), [
            'secret' => config('challenge.site_secret_mobile'),
            'response' => $value,
        ]);

        Log::debug('Turnstile response', [
            'response' => $response->json(),
        ], ['trait' => 'Turnstile']);

        return $response->json()['success'];
    }
}
