<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait WaApiMeta
{
    /**
     * Add phone number to AI exception reply
     * For now set to 30 minutes
     */
    public function addToAIExceptionReply(string $phoneNumber): void
    {
        Cache::add("ai:exception:reply:{$phoneNumber}", true, 1800);

        Log::debug('Added phone number to AI exception reply', ['phone_number' => $phoneNumber]);
    }
}
