<?php

namespace App\Interfaces;

use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\WaApiMeta;
use Carbon\Carbon;

class WaApiMetaInterfaceClass
{
    use WaApiMeta;

    /**
     * Check if a phone number has sent a message within the last 24 hours
     *
     * @param  string  $phoneNumber  The phone number to check (without + or spaces)
     * @return bool True if the number has sent a message in the last 24 hours
     */
    public function hasRecentMessage(string $phoneNumber): bool
    {
        // Find the most recent message received from this number
        $recentMessage = WaMessageWebhookLog::where('message_from', $phoneNumber)
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->latest()
            ->first();

        return $recentMessage !== null;
    }
}
