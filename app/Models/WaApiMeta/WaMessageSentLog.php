<?php

namespace App\Models\WaApiMeta;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class WaMessageSentLog extends Model
{
    use HasUuids, Prunable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipient_number',
        'message_content',
        'message_id',
        'preview_url',
        'success',
        'response_data',
        'error_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'preview_url' => 'boolean',
        'success' => 'boolean',
        'response_data' => 'array',
        'error_data' => 'array',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    /**
     * Get the message thread that this sent message belongs to.
     */
    public function thread(): MorphOne
    {
        return $this->morphOne(WaApiMessageThreads::class, 'messageable');
    }
}
