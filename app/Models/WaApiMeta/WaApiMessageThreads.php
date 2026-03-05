<?php

namespace App\Models\WaApiMeta;

use App\Models\ConversationTag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'status',
        'division',
        'assigned_agent_id',
        'handoff_requested_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_message_at' => 'datetime',
        'handoff_requested_at' => 'datetime',
    ];

    /**
     * Get the parent messageable model (sent log or webhook log).
     */
    public function messageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the assigned agent.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Get the tags for this conversation.
     */
    public function tags(): HasMany
    {
        return $this->hasMany(ConversationTag::class, 'conversation_id');
    }

    /**
     * Get all messages for a specific phone number thread.
     */
    public function scopeByPhoneNumber(Builder $query, string $phoneNumber): Builder
    {
        return $query->where('phone_number', $phoneNumber)->orderBy('last_message_at', 'desc');
    }

    /**
     * Filter conversations by status.
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter open conversations.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'OPEN');
    }

    /**
     * Filter pending human conversations.
     */
    public function scopePendingHuman(Builder $query): Builder
    {
        return $query->where('status', 'PENDING_HUMAN');
    }

    /**
     * Filter resolved conversations.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'RESOLVED');
    }

    /**
     * Check if conversation requires human response.
     */
    public function requiresHuman(): bool
    {
        return $this->status === 'PENDING_HUMAN';
    }

    /**
     * Check if conversation is assigned to an agent.
     */
    public function isAssigned(): bool
    {
        return ! is_null($this->assigned_agent_id);
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonth());
    }
}
