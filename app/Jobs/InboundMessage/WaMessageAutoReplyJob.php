<?php

namespace App\Jobs\InboundMessage;

use App\Interfaces\GeminiAiInterfaceClass;
use App\Interfaces\WaApiMetaInterfaceClass;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
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

            // Reply using Gemini AI if it's enabled but fallback on exception
            // to auto-reply if Gemini AI is not available or enabled
            // This is to ensure that auto-reply works even if Gemini AI is not configured or
            // if there are issues with the Gemini AI service.
            $message = "Hai! Terima kasih sudah menghubungi Template.\n\n"
                     ."Pesan ini adalah balasan otomatis. Saat ini kami tidak dapat membalas pesan Anda secara langsung, silahkan hubungi kami kembali di lain waktu.\n\n";

            $isAiReply = false;
            try {
                if (config('services.geminiai.enabled')) {
                    $ai = new GeminiAiInterfaceClass;
                    $cacheKey = 'wa:ai:conversation:'.$phoneNumber;
                    $conversation = Cache::get($cacheKey, []);

                    $prompt = $this->webhookLog->message_body;
                    if (is_null($prompt)) {
                        Log::warning('Gemini AI prompt is null, using default auto-reply.', [
                            'to' => $phoneNumber,
                        ]);
                    } else {
                        $aiReply = $ai->sendPrompt($prompt, $conversation);
                    }

                    if (! empty($aiReply)) {
                        $message = $aiReply;
                        $isAiReply = true;
                        Log::debug('Gemini AI auto-reply generated', [
                            'to' => $phoneNumber,
                            'reply' => $aiReply,
                        ]);

                        // Add last user message to conversation
                        $conversation[] = [
                            'role' => 'user',
                            'text' => $this->webhookLog->message_body,
                        ];

                        // Add model reply to conversation and cache it
                        $conversation[] = [
                            'role' => 'model',
                            'text' => $aiReply,
                        ];
                        Cache::put($cacheKey, $conversation, now()->addHours(1));
                    } else {
                        Log::warning('Gemini AI returned empty response, using default auto-reply.', [
                            'to' => $phoneNumber,
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Gemini AI auto-reply failed, using default auto-reply.', [
                    'to' => $phoneNumber,
                    'error' => $e->getMessage(),
                ]);
            }

            $whatsApp = new WaApiMetaInterfaceClass;

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
