<?php

namespace App\Jobs\WhatsApp;

use App\Interfaces\GoQueues;
use App\Traits\GoWorkerFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateTemplateJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $templateId,
        public array $updates,
        public ?string $userId = null
    ) {}

    /**
     * Get the unique ID for the job (for deduplication).
     */
    public function uniqueId(): string
    {
        return 'update-template-'.$this->templateId.'-'.md5(json_encode($this->updates));
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['UpdateTemplateJob', 'templateId: '.$this->templateId];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'UpdateTemplateJob', 'templateId' => $this->templateId]);

            $this->sendGoTask('template-update', [
                'template_id' => $this->templateId,
                'category' => $this->updates['category'] ?? null,
                'components' => $this->updates['components'] ?? null,
                'message_send_ttl_seconds' => $this->updates['message_send_ttl_seconds'] ?? null,
                'cta_url_link_tracking_opted_out' => $this->updates['cta_url_link_tracking_opted_out'] ?? null,
                'user_id' => $this->userId,
            ], GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'UpdateTemplateJob']);
        } catch (\Exception $e) {
            Log::error('Job Failed', ['jobName' => 'UpdateTemplateJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
