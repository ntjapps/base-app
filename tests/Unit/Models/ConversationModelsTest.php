<?php

use App\Models\AgentRoutingRule;
use App\Models\ConversationTag;
use App\Models\Passport\Client;
use App\Models\Permission;
use App\Models\User;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Observers\WaMessageSentLogObserver;
use App\Observers\WaMessageWebhookLogObserver;
use Illuminate\Support\Facades\Event;

describe('Conversation and User Models', function () {
    it('covers user helpers', function () {
        $u = User::factory()->create();
        expect($u->exceptConstPermission())->toBeArray();
        expect($u->prunable())->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class);
    });

    it('covers agent routing rules and conversation tags', function () {
        $u = User::factory()->create();
        $rule = AgentRoutingRule::create([
            'user_id' => $u->id,
            'division' => 'Sales',
            'priority' => 1,
            'enabled' => true,
        ]);
        expect($rule->user)->toBeInstanceOf(User::class);

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
            'status' => 'PENDING_HUMAN',
            'assigned_agent_id' => $u->id,
        ]);

        expect($thread->requiresHuman())->toBeTrue();
        expect($thread->isAssigned())->toBeTrue();
        expect($thread->messageable)->toBeInstanceOf(WaMessageWebhookLog::class);
        expect($thread->assignedAgent())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
        expect($thread->tags())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);

        $tag = ConversationTag::create(['conversation_id' => $thread->id, 'tag_name' => 'vip']);
        expect($tag->conversation())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('covers passport client and permission ability relation', function () {
        $c = new Client;
        $c->password_client = true;
        expect($c->skipsAuthorization(Mockery::mock(Illuminate\Contracts\Auth\Authenticatable::class), []))->toBeTrue();

        $p = new Permission;
        expect($p->ability())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphTo::class);
    });

    it('covers message log models and observers', function () {
        Event::fake();

        $sent = WaMessageSentLog::create([
            'recipient_number' => '6281',
            'message_content' => 'hi',
            'success' => true,
            'response_data' => [],
        ]);
        expect($sent->prunable())->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class);
        expect($sent->thread())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphOne::class);

        (new WaMessageSentLogObserver)->created($sent);
        expect(WaApiMessageThreads::where('messageable_id', $sent->id)->exists())->toBeTrue();

        (new WaMessageSentLogObserver)->deleted($sent);
        expect(WaApiMessageThreads::where('messageable_id', $sent->id)->exists())->toBeFalse();

        $webhook = WaMessageWebhookLog::create([
            'message_from' => '6282',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);
        expect($webhook->thread())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphOne::class);

        (new WaMessageWebhookLogObserver)->created($webhook);
        expect(WaApiMessageThreads::where('messageable_id', $webhook->id)->exists())->toBeTrue();

        (new WaMessageWebhookLogObserver)->deleted($webhook);
        expect(WaApiMessageThreads::where('messageable_id', $webhook->id)->exists())->toBeFalse();
    });

    it('covers thread scopes and prunable builders', function () {
        $u = User::factory()->create();

        $log = WaMessageWebhookLog::create([
            'message_from' => '6283',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => ['a' => 1],
        ]);
        $freshLog = WaMessageWebhookLog::findOrFail($log->id);
        expect($freshLog->raw_data)->toBeArray();
        expect($freshLog->prunable())->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class);

        $t1 = WaApiMessageThreads::create([
            'phone_number' => '6283',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now()->subMinutes(2),
            'status' => 'OPEN',
        ]);
        $t2 = WaApiMessageThreads::create([
            'phone_number' => '6283',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'RESOLVED',
            'assigned_agent_id' => $u->id,
        ]);
        $t3 = WaApiMessageThreads::create([
            'phone_number' => '000',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'PENDING_HUMAN',
        ]);

        expect(WaApiMessageThreads::byPhoneNumber('6283')->pluck('id')->all())->toContain($t1->id, $t2->id);
        expect(WaApiMessageThreads::byStatus('RESOLVED')->pluck('id')->all())->toContain($t2->id);
        expect(WaApiMessageThreads::open()->pluck('id')->all())->toContain($t1->id);
        expect(WaApiMessageThreads::pendingHuman()->pluck('id')->all())->toContain($t3->id);
        expect(WaApiMessageThreads::resolved()->pluck('id')->all())->toContain($t2->id);

        $old = WaApiMessageThreads::create([
            'phone_number' => '999',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now()->subDays(60),
            'status' => 'OPEN',
        ]);
        $old->created_at = now()->subMonths(2);
        $old->save();

        $model = new WaApiMessageThreads;
        $prunableIds = $model->prunable()->pluck('id')->all();
        expect($prunableIds)->toContain($old->id);
    });
});
