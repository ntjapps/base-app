<?php

namespace App\Interfaces;

use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\WaApiMeta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        /** Check if this phone number is in AI Exception */
        if (Cache::has("ai_exception_reply:{$to}")) {
            Log::info('Phone number is in AI exception reply', ['phone_number' => $to]);

            return null;
        }

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
