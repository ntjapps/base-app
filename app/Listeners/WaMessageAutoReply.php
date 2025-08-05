<?php

namespace App\Listeners;

use App\Events\WaMessageInboundEvent;
use App\Interfaces\WaApiMetaInterfaceClass;
use App\Models\WaApiMeta\WaMessageSentLog;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class WaMessageAutoReply implements ShouldQueue
{
    use InteractsWithQueue;

    public $afterCommit = true;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(WaMessageInboundEvent $event): void
    {
        $whatsApp = new WaApiMetaInterfaceClass;

        // Get the phone number from the webhook log
        $phoneNumber = $event->webhookLog->message_from;

        if (! $phoneNumber) {
            Log::warning('No valid phone number found in webhook data');

            return;
        }

        // Check if we've already sent an auto-reply to this number in the last 6 hours
        $sixHoursAgo = Carbon::now()->subHours(6);
        $recentAutoReply = WaMessageSentLog::where('recipient_number', $phoneNumber)
            ->where('created_at', '>=', $sixHoursAgo)
            ->first();

        if ($recentAutoReply) {
            Log::info('Skipping auto-reply: Already sent within the last 6 hours', [
                'to' => $phoneNumber,
                'last_sent' => $recentAutoReply->created_at->diffForHumans(),
            ]);

            return;
        }

        // Prepare the auto-reply message
        $message = "Hai! Terima kasih sudah menghubungi NTJ Application studio.\n\n"
                 ."Pesan ini adalah balasan otomatis. Saat ini kami tidak dapat membalas pesan Anda secara langsung, silahkan hubungi kami kembali di lain waktu.\n\n";

        try {
            // Send the auto-reply message
            $response = $whatsApp->sendMessage($phoneNumber, $message, true);

            if ($response) {
                Log::info('Auto-reply sent successfully', [
                    'to' => $phoneNumber,
                    'message_id' => $response['messages'][0]['id'] ?? null,
                ]);
            } else {
                Log::error('Failed to send auto-reply', [
                    'to' => $phoneNumber,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending auto-reply: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
