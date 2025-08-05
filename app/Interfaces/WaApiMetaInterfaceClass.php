<?php

namespace App\Interfaces;

use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\WaApiMeta;
use Illuminate\Support\Carbon;

class WaApiMetaInterfaceClass
{
    use WaApiMeta;

    /**
     * Send a WhatsApp message using the Meta Cloud API
     *
     * @param  string  $to  The recipient's phone number with country code (no + or spaces)
     * @param  string  $message  The message body text to send
     * @param  bool  $previewUrl  Whether to generate URL previews in the message (default: false)
     * @return array|null The API response or null on error
     */
    public function sendMessage(string $to, string $message, bool $previewUrl = false): ?array
    {
        // Call the protected trait method
        return $this->sendWhatsAppMessage($to, $message, $previewUrl);
    }

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
