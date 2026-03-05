<?php

namespace App\Models;

use App\Models\WaApiMeta\WaApiMessageThreads;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationTag extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'tag_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the conversation that owns the tag.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaApiMessageThreads::class, 'conversation_id');
    }
}
