<?php

namespace App\Models;

use App\Jobs\InvalidateGoCacheJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Boot method to clear cache on model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            InvalidateGoCacheJob::dispatch('division');
        });

        static::deleted(function ($model) {
            InvalidateGoCacheJob::dispatch('division');
        });
    }
}
