<?php

use App\Models\AiModelInstruction;
use Illuminate\Support\Facades\File;

it('returns instructions from DB and does not fallback to file', function () {
    // Ensure DB is clean
    AiModelInstruction::query()->delete();

    // Create a DB-backed instruction
    $inst = AiModelInstruction::create([
        'name' => 'Test Instruction',
        'key' => 'test_key',
        'instructions' => 'DB instructions',
        'enabled' => true,
    ]);

    $text = AiModelInstruction::getInstructionsText('test_key');
    expect($text)->toBe('DB instructions');

    // Now delete DB record and create a storage file; getInstructionsText should NOT fallback
    $inst->delete();

    $path = storage_path('model_instruction.txt');
    File::put($path, 'File instructions');

    $text = AiModelInstruction::getInstructionsText('test_key');
    expect($text)->toBeNull();

    // Clean up
    File::delete($path);
});
