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

class CreateTemplateJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $templateData,
        public ?string $userId = null
    ) {}

    /**
     * Get the unique ID for the job (for deduplication).
     */
    public function uniqueId(): string
    {
        return 'create-template-'.md5(json_encode($this->templateData));
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['CreateTemplateJob', 'template: '.$this->templateData['name'] ?? 'unknown'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'CreateTemplateJob', 'template' => $this->templateData['name'] ?? null]);

            $this->sendGoTask('template-create', [
                'name' => $this->templateData['name'],
                'language' => $this->templateData['language'],
                'category' => $this->templateData['category'],
                'components' => $this->templateData['components'],
                'message_send_ttl_seconds' => $this->templateData['message_send_ttl_seconds'] ?? null,
                'cta_url_link_tracking_opted_out' => $this->templateData['cta_url_link_tracking_opted_out'] ?? null,
                'user_id' => $this->userId,
            ], GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'CreateTemplateJob']);
        } catch (\Exception $e) {
            Log::error('Job Failed', ['jobName' => 'CreateTemplateJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
