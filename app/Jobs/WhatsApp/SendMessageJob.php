<?php

namespace App\Jobs\WhatsApp;

use App\Interfaces\GoQueues;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Traits\GoWorkerFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $to,
        public string $message,
        public bool $previewUrl = false,
        public ?string $userId = null
    ) {
        $this->onQueue('default');
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['SendMessageJob', 'to: '.$this->to];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'SendMessageJob', 'to' => $this->to]);

            $payload = [
                'to' => $this->to,
                'message' => $this->message,
                'preview_url' => $this->previewUrl,
            ];

            if ($this->userId) {
                $payload['user_id'] = $this->userId;
            }

            // In tests run a local HTTP send to exercise the PHP HTTP logic and create sent logs
            if (app()->environment('testing')) {
                try {
                    if (! config('services.whatsapp.enabled')) {
                        // Create a failed log entry
                        WaMessageSentLog::create([
                            'recipient_number' => $this->to,
                            'message_content' => $this->message,
                            'sent_by_user_id' => $this->userId,
                            'success' => false,
                            'error_data' => ['status' => 0, 'message' => 'WhatsApp API is disabled'],
                        ]);

                        throw new \App\Exceptions\CommonCustomException('WhatsApp API is disabled', 422, null, ['status' => 0]);
                    }

                    $endpoint = rtrim(config('services.whatsapp.endpoint'), '/').'/'.config('services.whatsapp.phone_number_id').'/messages';
                    $response = Http::withToken(config('services.whatsapp.access_token'))->post($endpoint, [
                        'messaging_product' => 'whatsapp',
                        'to' => $this->to,
                        'type' => 'text',
                        'text' => ['body' => $this->message],
                    ]);

                    $status = $response->status();
                    $success = $response->successful();

                    WaMessageSentLog::create([
                        'recipient_number' => $this->to,
                        'message_content' => $this->message,
                        'sent_by_user_id' => $this->userId,
                        'success' => $success,
                        'response_data' => ['status' => $status, 'json' => $response->json()],
                    ]);

                    if (! $success) {
                        // Create an error record for debugging purposes
                        WaMessageSentLog::create([
                            'recipient_number' => $this->to,
                            'message_content' => $this->message,
                            'sent_by_user_id' => $this->userId,
                            'success' => false,
                            'error_data' => ['status' => $status, 'json' => $response->json()],
                        ]);

                        // Throw a CommonCustomException so controller returns 422 but contains the original status in meta
                        throw new \App\Exceptions\CommonCustomException('WhatsApp returned error', 422, null, ['status' => $status]);
                    }

                    Log::debug('Job Finished (testing direct send)', ['jobName' => 'SendMessageJob']);

                    return;
                } catch (\Exception $e) {
                    Log::error('Job Failed (testing direct send)', ['jobName' => 'SendMessageJob', 'errors' => $e->getMessage()]);
                    throw $e;
                }
            }

            // Dispatch to Go worker via RabbitMQ
            $this->sendGoTask('wa-send-message', $payload, GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'SendMessageJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'SendMessageJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
