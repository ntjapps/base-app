
<?php

use App\Interfaces\GeminiAiInterfaceClass;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('ai:test {message}', function () {
    $message = $this->argument('message');
    $ai = new GeminiAiInterfaceClass;
    $this->info('Testing Gemini AI with message: '.$message);
    try {
        $response = $ai->sendPrompt($message);
        $this->info('Gemini AI Response:'.(is_array($response) ? json_encode($response, JSON_PRETTY_PRINT) : $response));
        Log::info('ai:test executed', ['message' => $message, 'response' => $response]);
    } catch (\Exception $e) {
        $this->error('Error: '.$e->getMessage());
        Log::error('ai:test error', ['message' => $message, 'error' => $e->getMessage()]);
    }
})->purpose('Test Gemini AI with a prompt message');

Artisan::command('ai:check-instruction', function () {
    $key = config('ai.instructions.default_key', 'whatsapp_default');
    $instruction = App\Models\AiModelInstruction::getInstructionsText($key);

    if ($instruction) {
        $this->info("AI instruction found for key: {$key}");
        $this->line('--- Instruction Content ---');
        $this->line($instruction);
    } else {
        $this->warn("No DB-backed instruction found for key: {$key}");
    }
})->purpose('Check DB-backed AI instruction for the default key and show its content');
