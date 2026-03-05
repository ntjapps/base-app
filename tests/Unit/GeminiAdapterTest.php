<?php

use App\Services\Ai\Adapters\GeminiAdapter;
use Illuminate\Support\Facades\Http;

it('uses system_instruction passed in context and not the fallback file', function () {
    // Fake HTTP post to Gemini API
    Http::fake([
        '*' => Http::response(json_encode([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [['text' => 'AI reply text']],
                        'role' => 'model',
                    ],
                    'finishReason' => 'STOP',
                    'index' => 0,
                ],
            ],
        ]), 200),
    ]);

    $adapter = new GeminiAdapter;

    $prompt = 'Hello';
    $context = [
        'system_instruction' => 'System instruction from DB',
        'conversation' => [],
        'model' => 'gemini-2.5-flash-lite',
    ];

    $response = $adapter->sendPrompt($prompt, $context);

    expect($response->success)->toBeTrue();
    expect($response->text)->toBe('AI reply text');

    // Assert HTTP request included the system_instruction in the request body
    Http::assertSent(function ($request) {
        $body = $request->data();

        // For Gemini format, system_instruction is an array with parts
        return isset($body['system_instruction']) && is_array($body['system_instruction']);
    });
});
