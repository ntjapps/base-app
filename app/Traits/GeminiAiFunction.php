<?php

namespace App\Traits;

use ErrorException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    private function generateContent(string $message, array $conversation = [], ?string $model = null, ?string $localFilePath = null, ?string $displayName = null, ?string $mimeType = null): string
    {
        $apiKey = config('services.geminiai.api_key');
        $baseUrl = config('services.geminiai.base_url');
        $model = $model ?? config('services.geminiai.selected_model');
        $url = "{$baseUrl}models/{$model}:generateContent";
        // If a local file path is provided, upload it first to get a file_uri
        $fileData = null;
        if (! empty($localFilePath)) {
            $displayName = $displayName ?? basename($localFilePath);
            $mimeType = $mimeType ?? (function_exists('mime_content_type') ? mime_content_type($localFilePath) : null) ?? 'application/octet-stream';

            $fileUri = $this->uploadFileAndGetUri($localFilePath, $displayName, $mimeType);
            $fileData = [
                'mime_type' => $mimeType,
                'file_uri' => $fileUri,
            ];
        }

        $response = Http::withHeaders([
            'X-goog-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->post($url, $this->buildGeminiRequestBody($message, $conversation, $fileData));

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

    /**
     * Build a Gemini API request body and optionally attach a file as part of the user content.
     *
     * Example $fileData:
     * [
     *   'mime_type' => 'text/csv',
     *   'file_uri' => 'https://generativelanguage.googleapis.com/v1beta/files/14kp91m75z5u'
     * ]
     */
    private function buildGeminiRequestBody(string $message, array $conversation = [], ?array $fileData = null): array
    {
        $instructionPath = storage_path('model_instruction.txt');
        if (file_exists($instructionPath)) {
            $instruction = file_get_contents($instructionPath);
        } else {
            $instruction = '';
        }

        $contents = [];
        // Add conversation history if provided
        foreach ($conversation as $item) {
            $contents[] = [
                'role' => $item['role'],
                'parts' => [
                    ['text' => $item['text']],
                ],
            ];
        }

        // Build user parts and include file_data if provided
        $userParts = [
            ['text' => $message],
        ];

        if ($fileData && isset($fileData['mime_type'], $fileData['file_uri'])) {
            $userParts[] = [
                'file_data' => [
                    'mime_type' => $fileData['mime_type'],
                    'file_uri' => $fileData['file_uri'],
                ],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => $userParts,
        ];

        return [
            'system_instruction' => [
                'parts' => [
                    ['text' => $instruction],
                ],
            ],
            'contents' => $contents,
        ];
    }

    /**
     * Upload a local file using the Gemini resumable upload flow and return the file URI.
     *
     * Flow:
     * 1) POST to {baseUrl}upload/v1beta/files?key={apiKey} with headers to start resumable upload.
     *    Expects response header 'x-goog-upload-url'.
     * 2) POST the binary to the returned upload URL with required headers to upload and finalize.
     *
     * @param  string  $filePath  Absolute path to local file to upload.
     * @param  string  $displayName  Display name to register for the uploaded file (e.g. 'DATA').
     * @param  string|null  $mimeType  Optional mime type; will be detected if omitted.
     * @return string The file URI (for example: https://generativelanguage.googleapis.com/v1beta/files/XXXX)
     *
     * @throws ErrorException on failure.
     */
    private function uploadFileAndGetUri(string $filePath, string $displayName, ?string $mimeType = null): string
    {
        if (! file_exists($filePath)) {
            throw new ErrorException("File not found: {$filePath}");
        }

        $apiKey = config('services.geminiai.api_key');
        $baseUrl = config('services.geminiai.file_upload_base_url').'?key='.$apiKey;

        $numBytes = filesize($filePath);
        $mimeType = $mimeType ?? (function_exists('mime_content_type') ? mime_content_type($filePath) : null) ?? 'application/octet-stream';

        // Start resumable upload to get the upload URL
        $startResponse = Http::withHeaders([
            'X-Goog-Upload-Protocol' => 'resumable',
            'X-Goog-Upload-Command' => 'start',
            'X-Goog-Upload-Header-Content-Length' => (string) $numBytes,
            'X-Goog-Upload-Header-Content-Type' => $mimeType,
            'Content-Type' => 'application/json',
        ])->post($baseUrl, [
            'file' => [
                'display_name' => $displayName,
            ],
        ]);

        if (! $startResponse->successful()) {
            Log::error('Gemini upload start failed: '.$startResponse->body());
            throw new ErrorException('Failed to initiate resumable upload: '.$startResponse->body());
        }

        $uploadUrl = $startResponse->header('x-goog-upload-url');
        if (empty($uploadUrl)) {
            Log::error('Missing x-goog-upload-url header in upload start response: '.$startResponse->body());
            throw new ErrorException('Missing upload URL from Gemini upload start response.');
        }

        // Read file contents
        $fileContents = file_get_contents($filePath);

        // Upload and finalize
        $uploadResponse = Http::withHeaders([
            'Content-Length' => (string) $numBytes,
            'X-Goog-Upload-Offset' => '0',
            'X-Goog-Upload-Command' => 'upload, finalize',
        ])->withBody($fileContents, $mimeType)->post($uploadUrl);

        if (! $uploadResponse->successful()) {
            Log::error('Gemini upload failed: '.$uploadResponse->body());
            throw new ErrorException('Failed to upload file to Gemini: '.$uploadResponse->body());
        }

        $json = $uploadResponse->json();
        if (isset($json['file']['uri'])) {
            return $json['file']['uri'];
        }

        Log::error('Gemini upload response missing file.uri: '.$uploadResponse->body());
        throw new ErrorException('Upload succeeded but response did not contain file.uri');
    }
}
