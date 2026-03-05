<?php

namespace App\Models;

use App\Jobs\InvalidateGoCacheJob;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AiModelInstruction extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'key',
        'instructions',
        'enabled',
        'scope',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'scope' => 'array',
    ];

    /**
     * Get instruction by key with caching.
     *
     * @param  int|null  $cacheDuration  Cache duration in seconds (null uses config default)
     */
    public static function getInstructionByKey(string $key, ?int $cacheDuration = null): ?static
    {
        $duration = $cacheDuration ?? config('ai.instructions.cache_duration', 3600);

        return Cache::remember(
            "ai_instruction:{$key}",
            $duration,
            fn () => static::where('key', $key)
                ->where('enabled', true)
                ->first()
        );
    }

    /**
     * Get instructions text by key from DB. No file fallback is supported.
     */
    public static function getInstructionsText(string $key): ?string
    {
        $instruction = static::getInstructionByKey($key);

        return $instruction ? $instruction->instructions : null;
    }

    /**
     * Clear cache for a specific key.
     */
    public static function clearCache(string $key): bool
    {
        // Dispatch job to invalidate Go cache
        InvalidateGoCacheJob::dispatch('instruction', $key);

        return Cache::forget("ai_instruction:{$key}");
    }

    /**
     * Boot method to clear cache on model events.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saved(function ($model) {
            static::clearCache($model->key);
        });

        static::deleted(function ($model) {
            static::clearCache($model->key);
        });
    }
}
