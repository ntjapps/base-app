<?php

use App\Jobs\WaApiCleanOrphanedThreadLog;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

describe('WaApiCleanOrphanedThreadLog', function () {
    it('skips when whatsapp service is disabled', function () {
        Config::set('services.whatsapp.enabled', false);

        WaApiMessageThreads::create([
            'phone_number' => '1',
            'messageable_id' => fake()->uuid(),
            'messageable_type' => WaMessageSentLog::class,
            'last_message_at' => now(),
        ]);

        (new WaApiCleanOrphanedThreadLog)->handle();
        expect(WaApiMessageThreads::count())->toBe(1);
    });

    it('deletes orphaned and invalid threads', function () {
        Config::set('services.whatsapp.enabled', true);

        $orphan = WaApiMessageThreads::create([
            'phone_number' => '1',
            'messageable_id' => fake()->uuid(),
            'messageable_type' => WaMessageSentLog::class,
            'last_message_at' => now(),
        ]);

        $invalidId = fake()->uuid();
        DB::table('wa_api_message_threads')->insert([
            'id' => $invalidId,
            'phone_number' => '1',
            'messageable_id' => fake()->uuid(),
            'messageable_type' => 'Invalid\\Type',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (new WaApiCleanOrphanedThreadLog)->handle();

        expect(WaApiMessageThreads::whereKey($orphan->id)->exists())->toBeFalse();
        expect(DB::table('wa_api_message_threads')->where('id', $invalidId)->exists())->toBeFalse();
    });

    it('preserves valid threads with existing WaMessageWebhookLog reference', function () {
        Config::set('services.whatsapp.enabled', true);

        $log = WaMessageWebhookLog::create([
            'message_from' => '6281',
            'message_body' => 'test',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $thread = WaApiMessageThreads::create([
            'phone_number' => '6281',
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
        ]);

        (new WaApiCleanOrphanedThreadLog)->handle();

        expect(WaApiMessageThreads::whereKey($thread->id)->exists())->toBeTrue();
    });

    it('deletes orphaned WaMessageWebhookLog thread when log is missing', function () {
        Config::set('services.whatsapp.enabled', true);

        $orphan = WaApiMessageThreads::create([
            'phone_number' => '6282',
            'messageable_id' => fake()->uuid(),
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
        ]);

        (new WaApiCleanOrphanedThreadLog)->handle();

        expect(WaApiMessageThreads::whereKey($orphan->id)->exists())->toBeFalse();
    });

    it('exposes queue metadata', function () {
        $job = new WaApiCleanOrphanedThreadLog;
        expect($job->uniqueId())->toBe('WaApiCleanOrphanedThreadLog');
        expect($job->tags())->toBeArray();
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(3);
        expect($job->uniqueFor)->toBe(60);
    });

    it('catches and rethrows on failure', function () {
        Config::set('services.whatsapp.enabled', true);
        Log::shouldReceive('debug')->andThrow(new RuntimeException('forced failure'));
        Log::shouldReceive('error')->once();

        (new WaApiCleanOrphanedThreadLog)->handle();
    })->throws(RuntimeException::class);
});
