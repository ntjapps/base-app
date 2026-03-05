<?php

use App\Jobs\PruneLogDebugLevelJob;
use App\Logger\Models\ServerLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;

describe('PruneLogDebugLevelJob', function () {
    it('prunes old logs and notifications', function () {
        $debug = ServerLog::create([
            'message' => 'd',
            'channel' => 'c',
            'level' => Logger::toMonologLevel('debug'),
            'level_name' => 'DEBUG',
            'datetime' => now(),
            'context' => [],
            'extra' => [],
        ]);
        ServerLog::whereKey($debug->id)->update(['created_at' => now()->subDays(8)]);

        $info = ServerLog::create([
            'message' => 'i',
            'channel' => 'c',
            'level' => Logger::toMonologLevel('info'),
            'level_name' => 'INFO',
            'datetime' => now(),
            'context' => [],
            'extra' => [],
        ]);
        ServerLog::whereKey($info->id)->update(['created_at' => now()->subDays(15)]);

        $keep = ServerLog::create([
            'message' => 'k',
            'channel' => 'c',
            'level' => Logger::toMonologLevel('warning'),
            'level_name' => 'WARNING',
            'datetime' => now(),
            'context' => [],
            'extra' => [],
        ]);

        DB::table('notifications')->insert([
            'id' => fake()->uuid(),
            'type' => 't',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => fake()->uuid(),
            'data' => json_encode(['x' => 1]),
            'created_at' => now()->subDays(8),
            'updated_at' => now()->subDays(8),
            'read_at' => null,
        ]);

        DB::table('notifications')->insert([
            'id' => fake()->uuid(),
            'type' => 't',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => fake()->uuid(),
            'data' => json_encode(['x' => 1]),
            'created_at' => now(),
            'updated_at' => now(),
            'read_at' => now()->subDays(2),
        ]);

        (new PruneLogDebugLevelJob)->handle();

        expect(ServerLog::whereKey($debug->id)->exists())->toBeFalse();
        expect(ServerLog::whereKey($info->id)->exists())->toBeFalse();
        expect(ServerLog::whereKey($keep->id)->exists())->toBeTrue();
    });

    it('rolls back and rethrows on failures', function () {
        DB::shouldReceive('beginTransaction')->once()->andThrow(new RuntimeException('boom'));
        DB::shouldReceive('rollBack')->andReturnNull()->byDefault();
        Log::shouldReceive('error')->once();
        Log::shouldReceive('debug')->andReturnNull();

        (new PruneLogDebugLevelJob)->handle();
    })->throws(RuntimeException::class);

    it('exposes queue metadata', function () {
        $job = new PruneLogDebugLevelJob;
        expect($job->uniqueId())->toBe('PruneLogDebugLevelJob');
        expect($job->tags())->toBeArray()->toContain('PruneLogDebugLevelJob');
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(3);
        expect($job->uniqueFor)->toBe(60);
    });
});
