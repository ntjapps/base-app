<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Laravel\Mcp\Server\Resource;

class ConversationHistoryResource extends Resource
{
    protected string $name = 'conversation-history';

    protected ?string $phone;

    protected int $limit;

    public function __construct(?string $phone = null, int $limit = 10)
    {
        $this->phone = $phone;
        $this->limit = min(max($limit, 1), 50); // Clamp between 1 and 50
    }

    public function description(): string
    {
        return 'Recent conversation history for WhatsApp support context';
    }

    public function handle(): string
    {
        if (! $this->phone) {
            return "# Conversation History\n\nNo phone number provided.";
        }

        // Get thread entries for this phone number
        $threads = WaApiMessageThreads::where('phone_number', $this->phone)
            ->orderBy('last_message_at', 'desc')
            ->limit($this->limit)
            ->get();

        if ($threads->isEmpty()) {
            return "# Conversation History\n\nNo conversation history found for phone: {$this->phone}";
        }

        $markdown = "# Conversation History\n\n";
        $markdown .= "**Phone:** {$this->phone}\n\n";
        $markdown .= "**Last {$threads->count()} messages:**\n\n";

        foreach ($threads as $thread) {
            $timestamp = $thread->last_message_at->toIso8601String();

            if ($thread->messageable_type === WaMessageWebhookLog::class) {
                $message = WaMessageWebhookLog::find($thread->messageable_id);
                if ($message) {
                    $markdown .= "- **[{$timestamp}] User:** {$message->message_body}\n";
                }
            } elseif ($thread->messageable_type === WaMessageSentLog::class) {
                $message = WaMessageSentLog::find($thread->messageable_id);
                if ($message) {
                    $status = $message->success ? '✓' : '✗';
                    $markdown .= "- **[{$timestamp}] Agent {$status}:** {$message->message_content}\n";
                }
            }
        }

        return $markdown;
    }

    public function uri(): string
    {
        return "whatsapp://conversation/{$this->phone}";
    }

    public function mimeType(): string
    {
        return 'text/markdown';
    }
}
