<?php

declare(strict_types=1);

namespace App\Services\Ai\Contracts;

interface AiProviderInterface
{
    /**
     * Send a prompt to the AI provider.
     *
     * @param  string  $prompt  The user prompt
     * @param  array<string, mixed>  $context  Additional context (system instructions, conversation history, etc.)
     */
    public function sendPrompt(string $prompt, array $context = []): AiProviderResponse;

    /**
     * Send a prompt with tool definitions for function-calling.
     *
     * @param  string  $prompt  The user prompt
     * @param  array<int, array<string, mixed>>  $tools  Array of tool schemas
     * @param  array<string, mixed>  $context  Additional context
     */
    public function sendPromptWithTools(string $prompt, array $tools, array $context = []): AiProviderResponse;

    /**
     * Check if the provider is enabled.
     */
    public function isEnabled(): bool;

    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Get the model identifier.
     */
    public function getModel(): string;
}
