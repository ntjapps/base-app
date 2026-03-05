<?php

use App\Services\Ai\Adapters\NullAdapter;
use App\Services\Ai\AiProviderFactory;
use Illuminate\Support\Facades\Config;

describe('AiProviderFactory', function () {
    it('creates providers by name', function () {
        $p = AiProviderFactory::make('null');
        expect($p)->toBeInstanceOf(NullAdapter::class);
    });

    it('throws for unknown provider', function () {
        AiProviderFactory::make('unknown');
    })->throws(InvalidArgumentException::class);

    it('returns NullAdapter when no providers are enabled', function () {
        Config::set('ai.default_provider', 'openai');
        Config::set('ai.providers.openai.enabled', false);
        Config::set('ai.providers.openai.api_key', '');
        Config::set('ai.providers.gemini.enabled', false);
        Config::set('ai.providers.gemini.api_key', '');

        $p = AiProviderFactory::makeDefault();
        expect($p)->toBeInstanceOf(NullAdapter::class);
    });
});
