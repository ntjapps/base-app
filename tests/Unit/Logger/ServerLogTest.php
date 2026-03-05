<?php

use App\Logger\Models\ServerLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject as EloquentArrayObject;
use Illuminate\Support\Arr;

describe('ServerLog', function () {
    it('builds a prunable query for old records', function () {
        Carbon::setTestNow(Carbon::parse('2026-03-02 12:00:00'));

        $old = ServerLog::create([
            'message' => 'm1',
            'channel' => 'c1',
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => 'd1',
            'context' => ['a' => 1],
            'extra' => ['b' => 2],
        ]);
        $old->created_at = now()->subMonths(3);
        $old->updated_at = now()->subMonths(3);
        $old->save();

        $new = ServerLog::create([
            'message' => 'm2',
            'channel' => 'c2',
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => 'd2',
            'context' => ['a' => 2],
            'extra' => ['b' => 3],
        ]);
        $new->created_at = now();
        $new->updated_at = now();
        $new->save();

        $model = new ServerLog;
        $query = $model->prunable();
        expect($query)->toBeInstanceOf(Builder::class);

        $ids = $query->pluck('id')->all();
        expect($ids)->toContain($old->id);
        expect($ids)->not->toContain($new->id);
    });

    it('casts context and extra as array objects', function () {
        $log = ServerLog::create([
            'message' => 'm3',
            'channel' => 'c3',
            'level' => 200,
            'level_name' => 'INFO',
            'datetime' => 'd3',
            'context' => ['x' => ['y' => 1]],
            'extra' => ['z' => 2],
        ]);

        $fresh = ServerLog::findOrFail($log->id);
        expect($fresh->context)->toBeInstanceOf(EloquentArrayObject::class);
        expect($fresh->extra)->toBeInstanceOf(EloquentArrayObject::class);
        expect(Arr::get($fresh->context->getArrayCopy(), 'x.y'))->toBe(1);
        expect($fresh->extra['z'])->toBe(2);
    });
});
