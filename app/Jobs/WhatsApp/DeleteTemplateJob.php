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

class DeleteTemplateJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $templateId,
        public string $templateName,
        public ?string $userId = null
    ) {}

    /**
     * Get the unique ID for the job (for deduplication).
     */
    public function uniqueId(): string
    {
        return 'delete-template-'.$this->templateId;
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return ['DeleteTemplateJob', 'templateId: '.$this->templateId, 'name: '.$this->templateName];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'DeleteTemplateJob', 'templateId' => $this->templateId, 'name' => $this->templateName]);

            $this->sendGoTask('template-delete', [
                'template_id' => $this->templateId,
                'template_name' => $this->templateName,
                'user_id' => $this->userId,
            ], GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'DeleteTemplateJob']);
        } catch (\Exception $e) {
            Log::error('Job Failed', ['jobName' => 'DeleteTemplateJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
