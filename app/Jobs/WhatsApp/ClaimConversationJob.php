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

class ClaimConversationJob implements ShouldQueue
{
    use Dispatchable, GoWorkerFunction, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $conversationId,
        public string $agentId
    ) {}

    /**
     * Get the tags that should be assigned to the job for displaying in Laravel Horizon.
     */
    public function tags(): array
    {
        return ['ClaimConversationJob', 'conversation: '.$this->conversationId, 'agent: '.$this->agentId];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::debug('Job Executed', ['jobName' => 'ClaimConversationJob', 'conversation_id' => $this->conversationId, 'agent_id' => $this->agentId]);

            $this->sendGoTask('conversation-claim', [
                'conversation_id' => $this->conversationId,
                'agent_id' => $this->agentId,
            ], GoQueues::WHATSAPP);

            Log::debug('Job Finished', ['jobName' => 'ClaimConversationJob']);
        } catch (\Exception $e) {
            Log::error('Job Failed', ['jobName' => 'ClaimConversationJob', 'errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]);

            throw $e;
        }
    }
}
