<?php

namespace App\Traits;

use ErrorException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

trait GeminiAiFunction
{
    /**
     * Generate content using Gemini AI API.
     *
     * @param  string  $message  The user message to send to Gemini AI.
     * @param  array  $conversation  Optional conversation history. Each item should be an associative array:
     *                               [
     *                               'role' => 'user'|'model',
     *                               'text' => 'Message text'
     *                               ]
     *                               Example:
     *                               [
     *                               ['role' => 'user', 'text' => 'Hello'],
     *                               ['role' => 'model', 'text' => 'Hi, how can I help you?'],
     *                               ['role' => 'user', 'text' => 'Tell me about NTJ Application Studio.']
     *                               ]
     *                               Gemini API response. Example structure:
     *                               [
     *                               'candidates' => [
     *                               [
     *                               'content' => [
     *                               'parts' => [
     *                               ['text' => '...response text...']
     *                               ],
     *                               'role' => 'model'
     *                               ],
     *                               'finishReason' => 'STOP',
     *                               'index' => 0
     *                               ]
     *                               ],
     *                               'usageMetadata' => [
     *                               'promptTokenCount' => 49,
     *                               'candidatesTokenCount' => 85,
     *                               'totalTokenCount' => 134,
     *                               'promptTokensDetails' => [
     *                               ['modality' => 'TEXT', 'tokenCount' => 49]
     *                               ]
     *                               ],
     *                               'modelVersion' => 'gemini-2.5-flash-lite',
     *                               'responseId' => 'Lv2TaPa7E9LOnsEPtvWy0Q8'
     *                               ]
     * @return string Gemini API response text.
     */
    private function generateContent(string $message, array $conversation = []): string
    {
        $apiKey = config('services.geminiai.api_key');
        $baseUrl = config('services.geminiai.base_url');
        $model = config('services.geminiai.selected_model');
        $url = "{$baseUrl}models/{$model}:generateContent";

        $instructionPath = storage_path('model_instruction.txt');
        if (file_exists($instructionPath)) {
            $instruction = file_get_contents($instructionPath);
        } else {
            Log::debug('model_instruction.txt not found in storage_path. Using empty instruction.');
            $instruction = '';
        }

        $contents = [];
        // Add conversation history if provided
        foreach ($conversation as $item) {
            // $item should be ['role' => 'user'|'model', 'text' => '...']
            $contents[] = [
                'role' => $item['role'],
                'parts' => [
                    ['text' => $item['text']],
                ],
            ];
        }
        // Add current message as user
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $message],
            ],
        ];

        $body = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $instruction],
                ],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ],
        ];

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->post($url, $body);

        if ($response->successful()) {
            Log::debug('GeminiAI response: '.$response->body());
            $json = $response->json();
            // Extract the AI response text from the first candidate
            if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                return $json['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::error('GeminiAI response does not contain expected text structure: '.$response->body());

                throw new ErrorException('GeminiAI response does not contain expected text structure.');
            }
        }

        Log::error('GeminiAI API error: '.$response->body());
        throw new ErrorException('GeminiAI API error: '.$response->body());
    }
}
