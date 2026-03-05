<?php

use App\Http\Controllers\WhatsappManController;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;

describe('WhatsappManController private helpers', function () {
    it('extracts message previews from nested data', function () {
        $c = new WhatsappManController;
        $ref = new ReflectionClass($c);

        $find = $ref->getMethod('findTextInNestedArray');
        $find->setAccessible(true);
        expect($find->invoke($c, ['text' => ['body' => 'hello']]))->toBe('hello');
        expect($find->invoke($c, ['body' => 'world']))->toBe('world');
        expect($find->invoke($c, ['x' => ['y' => []]]))->toBeNull();

        $extract = $ref->getMethod('extractMessagePreview');
        $extract->setAccessible(true);
        expect($extract->invoke($c, null))->toBeNull();

        $sent = WaMessageSentLog::create([
            'recipient_number' => '6281',
            'message_content' => 'hi there',
            'success' => true,
            'response_data' => [],
        ]);
        expect($extract->invoke($c, $sent))->toContain('hi');

        $webhook = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => '',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => ['text' => ['body' => 'nested']],
        ]);
        expect($extract->invoke($c, $webhook))->toContain('nested');
    });

    it('returns latest thread ids per phone', function () {
        $c = new WhatsappManController;
        $ref = new ReflectionClass($c);
        $m = $ref->getMethod('latestThreadIdsPerPhone');
        $m->setAccessible(true);

        $log1 = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'a',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);
        $log2 = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'b',
            'message_type' => 'text',
            'timestamp' => '2',
            'raw_data' => [],
        ]);

        $t1 = WaApiMessageThreads::create([
            'phone_number' => '6281',
            'messageable_id' => $log1->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now()->subMinute(),
        ]);
        $t2 = WaApiMessageThreads::create([
            'phone_number' => '6281',
            'messageable_id' => $log2->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
        ]);
        $t3 = WaApiMessageThreads::create([
            'phone_number' => '000',
            'messageable_id' => $log1->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
        ]);

        $ids = $m->invoke($c);
        expect($ids)->toBeInstanceOf(Illuminate\Support\Collection::class);
        expect($ids->contains($t2->id))->toBeTrue();
        expect($ids->contains($t3->id))->toBeTrue();
        expect($ids->contains($t1->id))->toBeFalse();
    });
});
