<?php

use App\Jobs\InboundMessage\WaMessageAutoReplyJob;
use App\Jobs\InboundMessage\WaMessageInboundJob;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

describe('WaMessageInboundJob', function () {
    it('dispatches WaMessageAutoReplyJob when phone is present', function () {
        Bus::fake();

        $log = WaMessageWebhookLog::create([
            'message_from' => '628123456789',
            'message_body' => 'hello',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        (new WaMessageInboundJob($log))->handle();

        Bus::assertDispatched(WaMessageAutoReplyJob::class);
    });

    it('skips dispatch when phone number is missing', function () {
        Bus::fake();

        $log = WaMessageWebhookLog::create([
            'message_from' => '',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        (new WaMessageInboundJob($log))->handle();

        Bus::assertNotDispatched(WaMessageAutoReplyJob::class);
    });

    it('exposes queue metadata', function () {
        $log = WaMessageWebhookLog::make([
            'message_from' => '628123456789',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        $job = new WaMessageInboundJob($log);
        expect($job->uniqueId())->toBe('WaMessageInboundJob');
        expect($job->tags())->toBeArray();
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(3);
    });

    it('catches and rethrows exception', function () {
        $log = WaMessageWebhookLog::create([
            'message_from' => '628123456790',
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        Log::shouldReceive('debug')->andThrow(new RuntimeException('forced failure'));
        Log::shouldReceive('error')->once();

        (new WaMessageInboundJob($log))->handle();
    })->throws(RuntimeException::class);
});
