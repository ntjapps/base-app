<?php

namespace App\Jobs\InboundMessage;

use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class WaMessageInboundJob implements ShouldQueue
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
        return 'WaMessageInboundJob';
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
        return ['WaMessageInboundJob', 'uniqueId: '.$this->uniqueId()];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'WaMessageInboundJob']);

            

            // Get the phone number from the webhook log
            $phoneNumber = $this->webhookLog->message_from;

            if (! $phoneNumber) {
                Log::warning('No valid phone number found in webhook data');

                return;
            }

            // Get the inbound message text
            $inboundMessage = $this->webhookLog->message_body ?? '';
            WaMessageAutoReplyJob::dispatch($this->webhookLog);

            

            Log::debug('Job Finished', ['jobName' => 'WaMessageInboundJob']);
        } catch (\Throwable $e) {
            

            Log::error('Job Failed', ['jobName' => 'WaMessageInboundJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
