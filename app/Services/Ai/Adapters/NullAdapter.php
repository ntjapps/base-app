<?php

declare(strict_types=1);

namespace App\Services\Ai\Adapters;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Contracts\AiProviderResponse;

class NullAdapter implements AiProviderInterface
{
    /**
     * Send a prompt (returns placeholder response).
     */
    public function sendPrompt(string $prompt, array $context = []): AiProviderResponse
    {
        return AiProviderResponse::success(
            'AI is currently disabled. This is a placeholder response.',
            null,
            ['provider' => 'null', 'timestamp' => now()->toIso8601String()]
        );
    }

    /**
     * Send a prompt with tools (returns placeholder response).
     */
    public function sendPromptWithTools(string $prompt, array $tools, array $context = []): AiProviderResponse
    {
        return $this->sendPrompt($prompt, $context);
    }

    /**
     * Check if enabled (always returns false).
     */
    public function isEnabled(): bool
    {
        return false;
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'null';
    }

    /**
     * Get the model identifier.
     */
    public function getModel(): string
    {
        return 'none';
    }
}
