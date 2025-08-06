<?php

namespace App\Jobs\InboundMessage;

use App\Interfaces\WaApiMetaInterfaceClass;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class WaMessageAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public WaMessageWebhookLog $webhookLog)
    {
        // $this->onQueue('default');
    }

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    // public $timeout = 60;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [1, 5, 10];
    }

    /**
     * Determine number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'WaMessageAutoReplyJob';
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 60;

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['WaMessageAutoReplyJob', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'WaMessageAutoReplyJob']);

            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::stopRecording();
            }

            // Get the phone number from the webhook log
            $phoneNumber = $this->webhookLog->message_from;

            // Check if we've already sent an auto-reply to this number in the last 1 hour
            $oneHourAgo = Carbon::now()->subHours(1);
            $recentAutoReply = WaMessageSentLog::where('recipient_number', $phoneNumber)
                ->where('created_at', '>=', $oneHourAgo)
                ->first();

            if ($recentAutoReply) {
                Log::info('Skipping auto-reply: Already sent within the last 1 hours', [
                    'to' => $phoneNumber,
                    'last_sent' => $recentAutoReply->created_at->diffForHumans(),
                ]);

                return;
            }

            $whatsApp = new WaApiMetaInterfaceClass;

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

            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }

            Log::debug('Job Finished', ['jobName' => 'WaMessageAutoReplyJob']);
        } catch (\Throwable $e) {
            /** Memory Leak mitigation */
            if (App::environment('local') && class_exists(\Laravel\Telescope\Telescope::class)) {
                \Laravel\Telescope\Telescope::startRecording();
            }

            Log::error('Job Failed', ['jobName' => 'WaMessageAutoReplyJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
