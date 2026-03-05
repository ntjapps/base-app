<?php

use App\Jobs\InboundMessage\NotifyHumanAgentJob;
use App\Jobs\InboundMessage\WaMessageAutoReplyJob;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\AiModelInstruction;
use App\Models\Division;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('WaMessageAutoReplyJob', function () {
    it('skips when manual reply exception is present', function () {
        $phone = '628123456789';
        Cache::put("ai:exception:reply:{$phone}", true, now()->addMinutes(5));

        $log = WaMessageWebhookLog::create([
            'message_from' => $phone,
            'message_body' => 'hi',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        Bus::fake();
        (new WaMessageAutoReplyJob($log))->handle();

        Bus::assertNotDispatched(SendMessageJob::class);
    });

    it('sends default message when AI providers are disabled', function () {
        $phone = '628123456789';
        Cache::forget("ai:exception:reply:{$phone}");

        Config::set('ai.default_provider', 'openai');
        Config::set('ai.providers.openai.enabled', false);
        Config::set('ai.providers.openai.api_key', '');
        Config::set('ai.providers.gemini.enabled', false);
        Config::set('ai.providers.gemini.api_key', '');

        $log = WaMessageWebhookLog::create([
            'message_from' => $phone,
            'message_body' => null,
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        Bus::fake();
        (new WaMessageAutoReplyJob($log))->handle();

        Bus::assertDispatched(SendMessageJob::class);
    });

    it('uses enabled AI provider, updates division, and requests handover', function () {
        $phone = '628123456789';
        Cache::forget("ai:exception:reply:{$phone}");

        AiModelInstruction::create([
            'name' => 'Default',
            'key' => 'whatsapp_default',
            'instructions' => 'You are helpful.',
            'enabled' => true,
            'scope' => null,
        ]);

        Division::create(['name' => 'Sales', 'description' => 'Sales', 'enabled' => true]);

        Cache::put('wa:ai:conversation:'.$phone, [
            ['role' => 'user', 'text' => 'hello'],
        ], now()->addMinutes(30));

        Config::set('ai.default_provider', 'openai');
        Config::set('ai.providers.openai.enabled', true);
        Config::set('ai.providers.openai.api_key', 'k');
        Config::set('ai.providers.openai.base_url', 'https://openai.test/');
        Config::set('ai.providers.openai.selected_model', 'gpt-test');

        Http::fake([
            'https://openai.test/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'Hello [DIVISION:SALES] [HANDOVER:HUMAN]'],
                ]],
            ], 200),
        ]);

        $log = WaMessageWebhookLog::create([
            'message_from' => $phone,
            'message_body' => 'Tell me about sales',
            'message_type' => 'text',
            'timestamp' => '1',
            'raw_data' => [],
        ]);

        WaApiMessageThreads::create([
            'phone_number' => $phone,
            'messageable_id' => $log->id,
            'messageable_type' => WaMessageWebhookLog::class,
            'last_message_at' => now(),
            'status' => 'OPEN',
        ]);

        Bus::fake();
        (new WaMessageAutoReplyJob($log))->handle();

        $thread = WaApiMessageThreads::byPhoneNumber($phone)->firstOrFail();
        expect($thread->division)->toBe('Sales');

        Bus::assertDispatched(SendMessageJob::class);
        Bus::assertDispatched(NotifyHumanAgentJob::class);
    });
});
