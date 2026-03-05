<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskStatus extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'task_name',
        'idempotency_key',
        'queue',
        'status',
        'payload',
        'result',
        'error_message',
        'attempt',
        'max_attempts',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Get the user that requested the task.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if task is in terminal state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    /**
     * Check if task is still pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'processing']);
    }
}
