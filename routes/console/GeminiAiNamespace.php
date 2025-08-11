
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
    $path = storage_path('model_instruction.txt');
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $this->info('model_instruction.txt exists at: '.$path);
        $this->line('--- File Content ---');
        $this->line($content);
    } else {
        $this->warn('model_instruction.txt does NOT exist at: '.$path);
    }
})->purpose('Check if Gemini AI instruction file exists and show its content');
