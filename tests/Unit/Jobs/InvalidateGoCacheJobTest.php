<?php

use App\Jobs\InvalidateGoCacheJob;
use Illuminate\Support\Facades\Log;

describe('InvalidateGoCacheJob', function () {
    it('exposes tags and runs handle without error', function () {
        $j = new InvalidateGoCacheJob('instruction', 'k1');
        expect($j->uniqueId())->toBe('InvalidateGoCacheJob');
        expect($j->tags())->toBeArray();
        $j->handle();
        expect(true)->toBeTrue();
    });

    it('catches and rethrows exception', function () {
        Log::shouldReceive('debug')->andThrow(new RuntimeException('forced failure'));
        Log::shouldReceive('error')->once();

        (new InvalidateGoCacheJob('instruction', 'k1'))->handle();
    })->throws(RuntimeException::class);
});
