<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WaTemplate extends Model
{
    use HasFactory, HasUuids, MassPrunable, SoftDeletes;

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonths(6));
    }

    protected $table = 'wa_templates';

    protected $fillable = [
        'provider_id',
        'name',
        'library_template_name',
        'language',
        'category',
        'sub_category',
        'components',
        'status',
        'quality_score',
        'rejected_reason',
        'message_send_ttl_seconds',
        'cta_url_link_tracking_opted_out',
        'parameter_format',
        'previous_category',
        'last_synced_at',
    ];

    protected $casts = [
        'components' => 'array',
        'quality_score' => 'integer',
        'message_send_ttl_seconds' => 'integer',
        'cta_url_link_tracking_opted_out' => 'boolean',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get all versions for this template.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(WaTemplateVersion::class, 'wa_template_id');
    }

    /**
     * Get the latest version.
     */
    public function latestVersion()
    {
        return $this->versions()->latest('version')->first();
    }

    /**
     * Check if template is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'APPROVED';
    }

    /**
     * Check if template is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if template is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'REJECTED';
    }
}
