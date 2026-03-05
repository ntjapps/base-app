<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Adapters\GeminiAdapter;
use App\Services\Ai\Adapters\NullAdapter;
use App\Services\Ai\Adapters\OpenAiAdapter;
use App\Services\Ai\Contracts\AiProviderInterface;
use InvalidArgumentException;

class AiProviderFactory
{
    /**
     * Create an AI provider instance based on configuration.
     *
     * @param  string|null  $provider  Provider name (gemini, openai, anthropic, null) or null for default
     *
     * @throws InvalidArgumentException
     */
    public static function make(?string $provider = null): AiProviderInterface
    {
        $provider = $provider ?? config('ai.default_provider', 'gemini');

        return match ($provider) {
            'gemini' => new GeminiAdapter,
            'openai' => new OpenAiAdapter,
            'null' => new NullAdapter,
            default => throw new InvalidArgumentException("Unknown AI provider: {$provider}"),
        };
    }

    /**
     * Get the first enabled provider from the configuration.
     */
    public static function makeDefault(): AiProviderInterface
    {
        $defaultProvider = config('ai.default_provider', 'gemini');

        // Try default provider first
        $provider = static::make($defaultProvider);
        if ($provider->isEnabled()) {
            return $provider;
        }

        // Fallback: try all providers
        foreach (['gemini', 'openai'] as $name) {
            if ($name === $defaultProvider) {
                continue;
            }

            $provider = static::make($name);
            if ($provider->isEnabled()) {
                return $provider;
            }
        }

        // If no providers are enabled, return NullAdapter
        return new NullAdapter;
    }
}
