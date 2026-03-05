<?php

namespace App\Jobs\InboundMessage;

use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\AiModelInstruction;
use App\Models\Division;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Services\Ai\AiProviderFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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

            // Get the phone number from the webhook log
            $phoneNumber = $this->webhookLog->message_from;

            // Check if this phone number has a manual reply exception (AI should not reply)
            if (Cache::has("ai:exception:reply:{$phoneNumber}")) {
                Log::info('Skipping AI auto-reply due to manual reply exception', [
                    'phone' => $phoneNumber,
                ]);

                return;
            }

            // Default fallback message
            $message = 'Hai! Terima kasih sudah menghubungi '.config('app.name')."\n\n"
                     ."Pesan ini adalah balasan otomatis. Saat ini kami tidak dapat membalas pesan Anda secara langsung, silahkan hubungi kami kembali di lain waktu.\n\n";

            $isAiReply = false;
            $provider = null;

            try {
                // Get AI provider (uses default or first enabled provider)
                $provider = AiProviderFactory::makeDefault();

                if ($provider->isEnabled()) {
                    // Get conversation cache
                    $cacheKey = 'wa:ai:conversation:'.$phoneNumber;
                    $conversation = Cache::get($cacheKey, []);

                    // Get AI instructions from database
                    $systemInstruction = AiModelInstruction::getInstructionsText(
                        config('ai.instructions.default_key', 'whatsapp_default')
                    );

                    // If no DB-backed instruction is found, use empty string (no fallback file)
                    $systemInstruction = $systemInstruction ?? '';

                    // Fetch enabled divisions from database and build dynamic instructions
                    $divisions = Division::where('enabled', true)->orderBy('name')->get();
                    if ($divisions->isNotEmpty()) {
                        $systemInstruction .= "\n\nIMPORTANT SYSTEM COMMANDS:\n";

                        // Build division commands dynamically
                        foreach ($divisions as $division) {
                            $divisionName = strtoupper($division->name);
                            $description = $division->description ?: $division->name;
                            $systemInstruction .= "- If the user asks about {$description}, output [DIVISION:{$divisionName}] at the end of your message.\n";
                        }

                        $systemInstruction .= "- If the user explicitly asks to speak to a human agent, output [HANDOVER:HUMAN] at the end of your message.\n";
                        $systemInstruction .= '- Do not output these tags if not applicable.';
                    } else {
                        // Fallback to basic handover command if no divisions configured
                        $systemInstruction .= "\n\nIMPORTANT SYSTEM COMMANDS:\n"
                            ."- If the user explicitly asks to speak to a human agent, output [HANDOVER:HUMAN] at the end of your message.\n"
                            .'- Do not output these tags if not applicable.';
                    }

                    $prompt = $this->webhookLog->message_body;
                    if (is_null($prompt)) {
                        Log::warning('Message body is null, using default auto-reply.', [
                            'to' => $phoneNumber,
                        ]);
                    } else {
                        // Build context with system instruction and conversation history
                        $context = [
                            'system_instruction' => $systemInstruction,
                            'conversation' => $conversation,
                        ];

                        // Send prompt to AI provider
                        $response = $provider->sendPrompt($prompt, $context);

                        if ($response->success && ! empty($response->text)) {
                            $message = $response->text;
                            $isAiReply = true;

                            // Parse and handle system commands - dynamically check for division tags
                            foreach ($divisions as $division) {
                                $divisionTag = '[DIVISION:'.strtoupper($division->name).']';
                                if (str_contains($message, $divisionTag)) {
                                    $message = str_replace($divisionTag, '', $message);
                                    $this->updateThreadDivision($phoneNumber, $division->name);
                                }
                            }

                            // Handle human handover
                            if (str_contains($message, '[HANDOVER:HUMAN]')) {
                                $message = str_replace('[HANDOVER:HUMAN]', '', $message);
                                $this->handleHumanHandover($phoneNumber);
                            }

                            $message = trim($message);

                            Log::info('AI auto-reply generated', [
                                'to' => $phoneNumber,
                                'provider' => $provider->getName(),
                                'model' => $provider->getModel(),
                                'metadata' => $response->metadata,
                            ]);

                            // Add last user message to conversation
                            $conversation[] = [
                                'role' => 'user',
                                'text' => $this->webhookLog->message_body,
                            ];

                            // Add model reply to conversation and cache it
                            $conversation[] = [
                                'role' => 'model',
                                'text' => $response->text,
                            ];
                            Cache::put($cacheKey, $conversation, now()->addHours(1));
                        } else {
                            Log::warning('AI provider returned unsuccessful response, using default auto-reply.', [
                                'to' => $phoneNumber,
                                'provider' => $provider->getName(),
                                'error' => $response->error,
                            ]);
                        }
                    }
                } else {
                    Log::info('No AI provider enabled, using default auto-reply.', [
                        'to' => $phoneNumber,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('AI auto-reply failed, using default auto-reply.', [
                    'to' => $phoneNumber,
                    'provider' => $provider?->getName(),
                    'error' => $e->getMessage(),
                ]);
            }

            // Dispatch message sending to Go worker via RabbitMQ
            SendMessageJob::dispatch($phoneNumber, $message, true);

            Log::info('Auto-reply queued for sending', [
                'to' => $phoneNumber,
                'is_ai_reply' => $isAiReply,
            ]);

            // Log to Slack (with try-catch to prevent crashes)
            try {
                Log::channel('slackwhatsapp')->info('WhatsApp auto-reply queued', [
                    'to' => $phoneNumber,
                    'message' => $message,
                    'is_ai_reply' => $isAiReply,
                ]);
            } catch (\Exception $slackException) {
                Log::debug('Failed to send WhatsApp auto-reply log to Slack', [
                    'error' => $slackException->getMessage(),
                ]);
            }

            Log::debug('Job Finished', ['jobName' => 'WaMessageAutoReplyJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'WaMessageAutoReplyJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update the thread division.
     */
    private function updateThreadDivision(string $phoneNumber, string $division): void
    {
        try {
            $thread = WaApiMessageThreads::byPhoneNumber($phoneNumber)->first();
            if ($thread) {
                $thread->update(['division' => $division]);
                Log::info('Thread division updated', ['phone' => $phoneNumber, 'division' => $division]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update thread division', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Handle human handover request.
     */
    private function handleHumanHandover(string $phoneNumber): void
    {
        try {
            $thread = WaApiMessageThreads::byPhoneNumber($phoneNumber)->first();
            if ($thread) {
                NotifyHumanAgentJob::dispatch($thread, 'AI detected user request for human agent');
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle human handover', ['phone' => $phoneNumber, 'error' => $e->getMessage()]);
        }
    }
}
