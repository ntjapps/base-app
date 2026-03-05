<?php

use App\Interfaces\RoleConstants;
use App\Jobs\WhatsApp\AddConversationTagsJob;
use App\Jobs\WhatsApp\ClaimConversationJob;
use App\Jobs\WhatsApp\RemoveConversationTagJob;
use App\Jobs\WhatsApp\ResolveConversationJob;
use App\Models\User;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;

describe('WhatsappManController conversation actions', function () {
    beforeEach(function () {
        $u = User::factory()->create();
        $u->syncRoles([RoleConstants::SUPER_ADMIN]);
        $this->actingAs($u, 'api');

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('authorize')->andReturnTrue();
    });

    it('returns stats and dispatches claim/resolve/tag jobs', function () {
        $log = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => ['text' => ['body' => 'hi']],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => '6281',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'OPEN',
        ]);

        $this->getJson(route('whatsapp-stats'))
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'open', 'pending', 'resolved']);

        Bus::fake();
        $this->postJson(route('whatsapp-conversation-claim'), ['conversation_id' => $thread->id])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->postJson(route('whatsapp-conversation-resolve'), ['conversation_id' => $thread->id])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->postJson(route('whatsapp-conversation-tags-add'), ['conversation_id' => $thread->id, 'tags' => ['vip']])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->deleteJson(route('whatsapp-conversation-tags-remove'), ['conversation_id' => $thread->id, 'tag_name' => 'vip'])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        Bus::assertDispatched(ClaimConversationJob::class);
        Bus::assertDispatched(ResolveConversationJob::class);
        Bus::assertDispatched(AddConversationTagsJob::class);
        Bus::assertDispatched(RemoveConversationTagJob::class);
    });
});
