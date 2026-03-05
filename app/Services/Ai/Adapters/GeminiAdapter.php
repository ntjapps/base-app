<?php

declare(strict_types=1);

namespace App\Services\Ai\Adapters;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Contracts\AiProviderResponse;
use App\Traits\GeminiAiFunction;
use Illuminate\Support\Facades\Log;

class GeminiAdapter implements AiProviderInterface
{
    use GeminiAiFunction;

    /**
     * Send a prompt to Gemini AI.
     */
    public function sendPrompt(string $prompt, array $context = []): AiProviderResponse
    {
        try {
            $conversation = $context['conversation'] ?? [];
            $model = $context['model'] ?? null;
            $systemInstruction = $context['system_instruction'] ?? null;

            $responseText = $this->generateContent($prompt, $conversation, $systemInstruction, $model);

            $metadata = [
                'provider' => 'gemini',
                'model' => $model ?? config('services.geminiai.selected_model'),
                'timestamp' => now()->toIso8601String(),
            ];

            return AiProviderResponse::success($responseText, null, $metadata);
        } catch (\Throwable $e) {
            Log::error('Gemini AI Provider Error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return AiProviderResponse::failure($e->getMessage(), [
                'provider' => 'gemini',
                'timestamp' => now()->toIso8601String(),
            ]);
        }
    }

    /**
     * Send a prompt with tool definitions.
     * Note: Gemini supports tool calling via function declarations.
     * This is a simplified implementation - full tool calling requires
     * additional Gemini API configuration.
     */
    public function sendPromptWithTools(string $prompt, array $tools, array $context = []): AiProviderResponse
    {
        // For now, we'll add tool definitions to the context and let Gemini
        // handle them through its function calling API when fully implemented.
        // This is a placeholder that falls back to regular prompt without tools.

        Log::warning('GeminiAdapter: Tool calling not fully implemented, falling back to regular prompt');

        return $this->sendPrompt($prompt, $context);
    }

    /**
     * Check if Gemini is enabled.
     */
    public function isEnabled(): bool
    {
        // Check both new config format and backward compatible services config
        return (bool) (config('ai.providers.gemini.enabled', false) || config('services.geminiai.enabled', false));
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'gemini';
    }

    /**
     * Get the model identifier.
     */
    public function getModel(): string
    {
        // Check both new config format and backward compatible services config
        return config('ai.providers.gemini.selected_model')
            ?? config('services.geminiai.selected_model', 'gemini-2.5-flash-lite');
    }
}
