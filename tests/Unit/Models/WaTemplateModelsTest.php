<?php

use App\Models\User;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Models\WaTemplate;
use App\Models\WaTemplateVersion;

describe('WhatsApp Template Models', function () {
    it('handles WaTemplate helpers and prunable builder', function () {
        $t = WaTemplate::create([
            'name' => 't1',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
        ]);

        expect($t->isApproved())->toBeTrue();
        expect($t->isRejected())->toBeFalse();
        expect($t->prunable())->toBeInstanceOf(Illuminate\Database\Eloquent\Builder::class);
    });

    it('auto-increments WaTemplateVersion version number', function () {
        $user = User::factory()->create();
        $t = WaTemplate::create([
            'name' => 't2',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'PENDING',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
        ]);

        $v1 = WaTemplateVersion::create([
            'wa_template_id' => $t->id,
            'snapshot' => ['status' => 'PENDING'],
            'changed_by_user_id' => $user->id,
        ]);

        $v2 = WaTemplateVersion::create([
            'wa_template_id' => $t->id,
            'snapshot' => ['status' => 'APPROVED'],
            'changed_by_user_id' => $user->id,
        ]);

        expect($v1->version)->toBe(1);
        expect($v2->version)->toBe(2);
        expect($v2->template())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('handles template status helpers, latest version, and casts', function () {
        $user = User::factory()->create();
        $t = WaTemplate::create([
            'name' => 't3',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'PENDING',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
            'quality_score' => '3',
            'message_send_ttl_seconds' => '10',
            'cta_url_link_tracking_opted_out' => 1,
            'last_synced_at' => now()->toDateTimeString(),
        ]);

        expect($t->isPending())->toBeTrue();
        expect($t->isApproved())->toBeFalse();
        expect($t->isRejected())->toBeFalse();

        WaTemplateVersion::create([
            'wa_template_id' => $t->id,
            'snapshot' => ['status' => 'PENDING'],
            'changed_by_user_id' => $user->id,
        ]);
        $v2 = WaTemplateVersion::create([
            'wa_template_id' => $t->id,
            'snapshot' => ['status' => 'APPROVED'],
            'changed_by_user_id' => $user->id,
        ]);

        expect($t->versions())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
        expect($t->latestVersion()->id)->toBe($v2->id);

        $fresh = WaTemplate::findOrFail($t->id);
        expect($fresh->components)->toBeArray();
        expect($fresh->quality_score)->toBeInt();
        expect($fresh->message_send_ttl_seconds)->toBeInt();
        expect($fresh->cta_url_link_tracking_opted_out)->toBeBool();
        expect($fresh->last_synced_at)->toBeInstanceOf(Carbon\Carbon::class);
    });

    it('prunes soft-deleted templates older than 6 months', function () {
        $old = WaTemplate::create([
            'name' => 't4',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'REJECTED',
            'components' => [],
        ]);
        $new = WaTemplate::create([
            'name' => 't5',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'REJECTED',
            'components' => [],
        ]);

        $old->delete();
        $new->delete();

        $oldTrashed = WaTemplate::withTrashed()->findOrFail($old->id);
        $oldTrashed->deleted_at = now()->subMonths(7);
        $oldTrashed->save();

        $newTrashed = WaTemplate::withTrashed()->findOrFail($new->id);
        $newTrashed->deleted_at = now()->subMonths(2);
        $newTrashed->save();

        $model = new WaTemplate;
        $ids = $model->prunable()->pluck('id')->all();
        expect($ids)->toContain($old->id);
        expect($ids)->not->toContain($new->id);
    });

    it('exposes thread relationship for inbound webhook logs', function () {
        $phone = '628123456789';
        $log = WaMessageWebhookLog::create([
            'message_from' => $phone,
            'message_body' => 'hello',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => $phone,
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
        ]);

        expect($log->thread)->not->toBeNull();
        expect($log->thread->id)->toBe($thread->id);
    });
});
