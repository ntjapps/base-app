<?php

declare(strict_types=1);

namespace App\Services\Ai\Adapters;

use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\Contracts\AiProviderResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiAdapter implements AiProviderInterface
{
    /**
     * Send a prompt to OpenAI.
     */
    public function sendPrompt(string $prompt, array $context = []): AiProviderResponse
    {
        if (! $this->isEnabled()) {
            return AiProviderResponse::failure('OpenAI provider is not enabled');
        }

        try {
            $messages = $this->buildMessages($prompt, $context);

            $response = Http::withToken(config('ai.providers.openai.api_key'))
                ->timeout(config('ai.providers.openai.timeout', 30))
                ->post(config('ai.providers.openai.base_url').'chat/completions', [
                    'model' => $this->getModel(),
                    'messages' => $messages,
                    'temperature' => $context['temperature'] ?? 0.7,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';

                $metadata = [
                    'provider' => 'openai',
                    'model' => $this->getModel(),
                    'usage' => $data['usage'] ?? null,
                    'timestamp' => now()->toIso8601String(),
                ];

                return AiProviderResponse::success($text, null, $metadata);
            }

            return AiProviderResponse::failure('OpenAI API error: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('OpenAI Provider Error', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return AiProviderResponse::failure($e->getMessage());
        }
    }

    /**
     * Send a prompt with tool definitions (function calling).
     */
    public function sendPromptWithTools(string $prompt, array $tools, array $context = []): AiProviderResponse
    {
        if (! $this->isEnabled()) {
            return AiProviderResponse::failure('OpenAI provider is not enabled');
        }

        try {
            $messages = $this->buildMessages($prompt, $context);
            $functions = $this->convertToolsToFunctions($tools);

            $response = Http::withToken(config('ai.providers.openai.api_key'))
                ->timeout(config('ai.providers.openai.timeout', 30))
                ->post(config('ai.providers.openai.base_url').'chat/completions', [
                    'model' => $this->getModel(),
                    'messages' => $messages,
                    'tools' => $functions,
                    'tool_choice' => 'auto',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $message = $data['choices'][0]['message'] ?? [];

                $text = $message['content'] ?? '';
                $toolCalls = $message['tool_calls'] ?? null;

                $metadata = [
                    'provider' => 'openai',
                    'model' => $this->getModel(),
                    'usage' => $data['usage'] ?? null,
                    'timestamp' => now()->toIso8601String(),
                ];

                return AiProviderResponse::success($text, $toolCalls, $metadata);
            }

            return AiProviderResponse::failure('OpenAI API error: '.$response->body());
        } catch (\Throwable $e) {
            Log::error('OpenAI Provider Error with Tools', [
                'error' => $e->getMessage(),
                'prompt' => $prompt,
            ]);

            return AiProviderResponse::failure($e->getMessage());
        }
    }

    /**
     * Check if OpenAI is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('ai.providers.openai.enabled', false)
            && ! empty(config('ai.providers.openai.api_key'));
    }

    /**
     * Get provider name.
     */
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * Get the model identifier.
     */
    public function getModel(): string
    {
        return config('ai.providers.openai.selected_model', 'gpt-4-turbo-preview');
    }

    /**
     * Build messages array from prompt and context.
     */
    private function buildMessages(string $prompt, array $context): array
    {
        $messages = [];

        // Add system instruction if provided
        if (isset($context['system_instruction'])) {
            $messages[] = [
                'role' => 'system',
                'content' => $context['system_instruction'],
            ];
        }

        // Add conversation history if provided
        if (isset($context['conversation'])) {
            foreach ($context['conversation'] as $item) {
                $messages[] = [
                    'role' => $item['role'] === 'model' ? 'assistant' : $item['role'],
                    'content' => $item['text'],
                ];
            }
        }

        // Add current prompt
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        return $messages;
    }

    /**
     * Convert MCP tool schemas to OpenAI function format.
     */
    private function convertToolsToFunctions(array $tools): array
    {
        return array_map(function ($tool) {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool['name'],
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['inputSchema'] ?? ['type' => 'object', 'properties' => []],
                ],
            ];
        }, $tools);
    }
}
