<?php

use App\Jobs\InvalidateGoCacheJob;
use App\Models\AiModelInstruction;
use App\Models\Division;
use Illuminate\Support\Facades\Bus;

it('dispatches InvalidateGoCacheJob when AiModelInstruction is saved', function () {
    Bus::fake();

    $inst = AiModelInstruction::create([
        'name' => 'Test',
        'key' => 'test_key',
        'instructions' => 'text',
        'enabled' => true,
    ]);

    Bus::assertDispatched(InvalidateGoCacheJob::class, function ($job) use ($inst) {
        return $job->type === 'instruction' && $job->key === $inst->key;
    });
});

it('dispatches InvalidateGoCacheJob when Division is saved', function () {
    Bus::fake();

    $div = Division::create([
        'name' => 'HR',
        'description' => 'Human Resources',
        'enabled' => true,
    ]);

    Bus::assertDispatched(InvalidateGoCacheJob::class, function ($job) {
        return $job->type === 'division' && $job->key === null;
    });
});
