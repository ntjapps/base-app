<?php

namespace App\Models\WaApiMeta;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WaApiMessageThreads extends Model
{
    use HasUuids, Prunable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'messageable_id',
        'messageable_type',
        'last_message_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the parent messageable model (sent log or webhook log).
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all messages for a specific phone number thread.
     */
    public function scopeByPhoneNumber(Builder $query, string $phoneNumber): Builder
    {
        return $query->where('phone_number', $phoneNumber)->orderBy('last_message_at', 'desc');
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonth());
    }
}
