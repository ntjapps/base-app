<?php

namespace App\Interfaces;

use App\Traits\GeminiAiFunction;

class GeminiAiInterfaceClass
{
    use GeminiAiFunction;

    /**
     * Send a prompt to Gemini AI.
     *
     * @param  string  $message  The user message to send.
     * @param  array  $conversation  Optional conversation history.
     * @return string Gemini API response.
     */
    public function sendPrompt(string $message, array $conversation = []): string
    {
        return $this->generateContent($message, $conversation);
    }
}
