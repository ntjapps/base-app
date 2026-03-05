<?php

namespace App\Jobs\InboundMessage;

use App\Models\ConversationTag;
use App\Models\Tag;
use App\Models\WaApiMeta\WaApiMessageThreads;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyHumanAgentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WaApiMessageThreads|string $conversation,
        public string $reason = 'User requested human agent'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (is_string($this->conversation)) {
            $this->conversation = WaApiMessageThreads::findOrFail($this->conversation);
        }

        try {
            Log::info('Human agent handoff initiated', [
                'conversation_id' => $this->conversation->id,
                'phone_number' => $this->conversation->phone_number,
                'reason' => $this->reason,
            ]);

            // Update conversation status
            $this->conversation->update([
                'status' => 'PENDING_HUMAN',
                'handoff_requested_at' => now(),
            ]);

            // Add handoff tag - check if 'human-handoff' tag exists in database, otherwise use default
            $handoffTag = Tag::where('name', 'human-handoff')->where('enabled', true)->first();
            $tagName = $handoffTag ? $handoffTag->name : 'human-handoff';

            ConversationTag::create([
                'conversation_id' => $this->conversation->id,
                'tag_name' => $tagName,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process human agent handoff', [
                'error' => $e->getMessage(),
                'conversation_id' => $this->conversation instanceof WaApiMessageThreads ? $this->conversation->id : $this->conversation,
            ]);
            throw $e;
        }
    }
}
