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

class SendTemplateMessageJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $to,
        public string $templateName,
        public array $components = [],
        public string $languageCode = 'id',
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
        return ['SendTemplateMessageJob', 'to: '.$this->to, 'template: '.$this->templateName];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'SendTemplateMessageJob', 'to' => $this->to, 'template' => $this->templateName]);

            $payload = [
                'to' => $this->to,
                'template_name' => $this->templateName,
                'language_code' => $this->languageCode,
                'components' => $this->components,
            ];

            if ($this->userId) {
                $payload['user_id'] = $this->userId;
            }

            // Dispatch to Go worker via RabbitMQ
            $this->sendGoTask('wa-send-template-message', $payload, GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'SendTemplateMessageJob']);
        } catch (\Throwable $e) {
            Log::error('Job Failed', ['jobName' => 'SendTemplateMessageJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);
            throw $e;
        }
    }
}
