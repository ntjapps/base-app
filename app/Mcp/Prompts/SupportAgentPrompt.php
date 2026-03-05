<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use App\Models\AiModelInstruction;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Prompt;

class SupportAgentPrompt extends Prompt
{
    protected string $name = 'support-agent';

    protected string $description = 'AI system instructions for WhatsApp support agent behavior. Fetches instructions from database only.';

    public function handle(Request $request): Response
    {
        $request->validate([
            'key' => 'sometimes|string',
            'context' => 'sometimes|array',
        ]);

        $key = $request->get('key', config('ai.instructions.default_key', 'whatsapp_default'));
        $context = $request->get('context', []);

        // Fetch instruction from database with caching
        $instructionText = AiModelInstruction::getInstructionsText($key);

        if (! $instructionText) {
            return Response::text('No AI instructions found for key: '.$key);
        }

        // Optionally inject context variables into instructions
        if (! empty($context)) {
            foreach ($context as $placeholder => $value) {
                $instructionText = str_replace('{{'.$placeholder.'}}', $value, $instructionText);
            }
        }

        return Response::text($instructionText);
    }

    public function arguments(): array
    {
        return [
            'key' => [
                'description' => 'The instruction key to fetch (e.g., whatsapp_default, support_flow)',
                'required' => false,
            ],
            'context' => [
                'description' => 'Optional context variables to inject into instructions (e.g., user_name, division)',
                'required' => false,
            ],
        ];
    }
}
