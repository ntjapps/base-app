<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class GetConversationHistoryTool extends Tool
{
    protected string $name = 'get-conversation-history';

    protected string $description = 'Retrieve recent conversation history for a phone number. Returns last N messages with timestamps and direction (incoming/outgoing).';

    public function handle(Request $request): ResponseFactory
    {
        $request->validate([
            'phone' => 'required|string',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $phone = $request->get('phone');
        $limit = $request->get('limit', 10);

        // Log tool invocation for audit
        Log::channel(config('ai.tools.logging.channel', 'stack'))
            ->info('AI Tool Invoked: GetConversationHistory', [
                'tool' => $this->name,
                'phone' => $phone,
                'limit' => $limit,
                'timestamp' => now(),
            ]);

        // Get thread entries for this phone number
        $threads = WaApiMessageThreads::where('phone_number', $phone)
            ->orderBy('last_message_at', 'desc')
            ->limit($limit)
            ->get();

        $messages = [];

        foreach ($threads as $thread) {
            // Determine if this is incoming or outgoing based on messageable type
            if ($thread->messageable_type === WaMessageWebhookLog::class) {
                $message = WaMessageWebhookLog::find($thread->messageable_id);
                if ($message) {
                    $messages[] = [
                        'direction' => 'incoming',
                        'timestamp' => $thread->last_message_at->toIso8601String(),
                        'message' => $message->message_body ?? '',
                        'type' => $message->message_type ?? 'text',
                    ];
                }
            } elseif ($thread->messageable_type === WaMessageSentLog::class) {
                $message = WaMessageSentLog::find($thread->messageable_id);
                if ($message) {
                    $messages[] = [
                        'direction' => 'outgoing',
                        'timestamp' => $thread->last_message_at->toIso8601String(),
                        'message' => $message->message_content ?? '',
                        'success' => $message->success ?? false,
                    ];
                }
            }
        }

        return Response::make(Response::json([
            'phone' => $phone,
            'total_messages' => count($messages),
            'messages' => $messages,
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'phone' => $schema->string()
                ->description('The phone number to retrieve conversation history for')
                ->required(),
            'limit' => $schema->integer()
                ->description('Maximum number of messages to return (1-50, default: 10)')
                ->min(1)
                ->max(50),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'phone' => $schema->string()
                ->description('The phone number'),
            'total_messages' => $schema->integer()
                ->description('Total number of messages returned'),
            'messages' => $schema->array()
                ->description('Array of message objects with direction, timestamp, and content'),
        ];
    }
}
