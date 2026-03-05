<?php

use App\Jobs\InboundMessage\NotifyHumanAgentJob;
use App\Models\ConversationTag;
use App\Models\Tag;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Log;

describe('NotifyHumanAgentJob', function () {
    it('updates conversation and adds tag (object input)', function () {
        Tag::create(['name' => 'human-handoff', 'description' => 'h', 'color' => '#000000', 'enabled' => true, 'is_system' => true]);

        $log = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => '6281',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'OPEN',
        ]);

        (new NotifyHumanAgentJob($thread, 'reason'))->handle();

        $thread->refresh();
        expect($thread->status)->toBe('PENDING_HUMAN');
        expect($thread->handoff_requested_at)->not->toBeNull();
        expect(ConversationTag::where('conversation_id', $thread->id)->where('tag_name', 'human-handoff')->exists())->toBeTrue();
    });

    it('resolves conversation from id string and uses default tag name', function () {
        Tag::query()->delete();

        $log = WaMessageWebhookLog::create([
            'message_from' => '6282',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => '6282',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'OPEN',
        ]);

        (new NotifyHumanAgentJob($thread->id))->handle();

        expect(ConversationTag::where('conversation_id', $thread->id)->where('tag_name', 'human-handoff')->exists())->toBeTrue();
    });

    it('catches and rethrows exception from inside the try block', function () {
        $log = WaMessageWebhookLog::create([
            'message_from' => '6283',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => '6283',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'OPEN',
        ]);

        Log::shouldReceive('info')->andThrow(new RuntimeException('forced log failure'));
        Log::shouldReceive('error')->once();

        (new NotifyHumanAgentJob($thread))->handle();
    })->throws(RuntimeException::class);
});
