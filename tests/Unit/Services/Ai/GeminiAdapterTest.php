<?php

use App\Services\Ai\Adapters\GeminiAdapter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('GeminiAdapter', function () {
    beforeEach(function () {
        Config::set('services.geminiai.api_key', 'k');
        Config::set('services.geminiai.base_url', 'https://gemini.test/');
        Config::set('services.geminiai.selected_model', 'm1');
    });

    it('returns success response on valid API response', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'hello']]],
                ]],
            ], 200),
        ]);

        $a = new GeminiAdapter;
        $res = $a->sendPrompt('hi', [
            'conversation' => [['role' => 'user', 'text' => 'u1']],
            'system_instruction' => 'sys',
        ]);

        expect($res->success)->toBeTrue();
        expect($res->text)->toBe('hello');
    });

    it('returns failure response on API error', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response(['error' => 'bad'], 500),
        ]);

        $a = new GeminiAdapter;
        $res = $a->sendPrompt('hi');
        expect($res->success)->toBeFalse();
    });

    it('supports tool call fallback and config toggles', function () {
        Http::fake([
            'https://gemini.test/models/m1:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'hello']]],
                ]],
            ], 200),
        ]);

        Config::set('ai.providers.gemini.enabled', true);
        Config::set('ai.providers.gemini.selected_model', 'm2');

        $a = new GeminiAdapter;
        expect($a->isEnabled())->toBeTrue();
        expect($a->getModel())->toBe('m2');

        $res = $a->sendPromptWithTools('hi', [['name' => 't']]);
        expect($res->success)->toBeTrue();
    });
});
