<?php

use App\Services\Ai\Adapters\OpenAiAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('OpenAiAdapter', function () {
    it('returns failure when disabled', function () {
        Config::set('ai.providers.openai.enabled', false);
        Config::set('ai.providers.openai.api_key', '');
        Config::set('ai.providers.openai.base_url', 'https://openai.test/');
        $a = new OpenAiAdapter;

        $res = $a->sendPrompt('hi');
        expect($res->success)->toBeFalse();
    });

    it('sends prompt and returns successful response', function () {
        Config::set('ai.providers.openai.enabled', true);
        Config::set('ai.providers.openai.api_key', 'k');
        Config::set('ai.providers.openai.base_url', 'https://openai.test/');
        Config::set('ai.providers.openai.selected_model', 'gpt-test');

        Http::fake([
            'https://openai.test/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['content' => 'hello'],
                ]],
                'usage' => ['total_tokens' => 1],
            ], 200),
        ]);

        $a = new OpenAiAdapter;
        $res = $a->sendPrompt('hi', [
            'system_instruction' => 'sys',
            'conversation' => [
                ['role' => 'user', 'text' => 'u1'],
                ['role' => 'model', 'text' => 'm1'],
            ],
        ]);

        expect($res->success)->toBeTrue();
        expect($res->text)->toBe('hello');
        expect($res->getMeta('provider'))->toBe('openai');
    });

    it('sends prompt with tools and returns tool calls', function () {
        Config::set('ai.providers.openai.enabled', true);
        Config::set('ai.providers.openai.api_key', 'k');
        Config::set('ai.providers.openai.base_url', 'https://openai.test/');
        Config::set('ai.providers.openai.selected_model', 'gpt-test');

        Http::fake([
            'https://openai.test/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => 'ok',
                        'tool_calls' => [['id' => 'c1']],
                    ],
                ]],
            ], 200),
        ]);

        $a = new OpenAiAdapter;
        $res = $a->sendPromptWithTools('hi', [
            ['name' => 'get_user', 'description' => 'd', 'inputSchema' => ['type' => 'object', 'properties' => []]],
        ]);

        expect($res->success)->toBeTrue();
        expect($res->toolCalls)->toBeArray();
    });
});
