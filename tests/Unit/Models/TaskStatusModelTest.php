<?php

use App\Models\TaskStatus;
use App\Models\User;

describe('TaskStatus', function () {
    it('casts fields and exposes user relationship', function () {
        $u = User::factory()->create();
        $ts = TaskStatus::create([
            'task_name' => 't1',
            'idempotency_key' => 'k1',
            'queue' => 'q1',
            'status' => 'queued',
            'payload' => ['a' => 1],
            'result' => ['b' => 2],
            'attempt' => 0,
            'max_attempts' => 1,
            'queued_at' => now(),
            'user_id' => $u->id,
        ]);

        $fresh = TaskStatus::findOrFail($ts->id);
        expect($fresh->payload)->toBeArray();
        expect($fresh->result)->toBeArray();
        expect($fresh->queued_at)->toBeInstanceOf(Carbon\Carbon::class);
        expect($fresh->user->id)->toBe($u->id);
    });

    it('detects pending and terminal states', function () {
        $ts = TaskStatus::create([
            'task_name' => 't2',
            'idempotency_key' => 'k2',
            'queue' => 'q1',
            'status' => 'queued',
            'payload' => [],
            'attempt' => 0,
            'max_attempts' => 1,
        ]);

        expect($ts->isPending())->toBeTrue();
        expect($ts->isTerminal())->toBeFalse();

        $ts->status = 'processing';
        expect($ts->isPending())->toBeTrue();

        $ts->status = 'completed';
        expect($ts->isPending())->toBeFalse();
        expect($ts->isTerminal())->toBeTrue();

        $ts->status = 'failed';
        expect($ts->isTerminal())->toBeTrue();
    });
});
