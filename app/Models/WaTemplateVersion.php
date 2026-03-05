<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaTemplateVersion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'wa_template_versions';

    public $timestamps = false;

    protected $fillable = [
        'wa_template_id',
        'version',
        'snapshot',
        'changed_by_user_id',
        'change_reason',
        'provider_event',
        'created_at',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'provider_event' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get the template this version belongs to.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WaTemplate::class, 'wa_template_id');
    }

    /**
     * Get the user who made this change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Boot method to auto-set created_at and version.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($version) {
            if (! $version->created_at) {
                $version->created_at = now();
            }

            // Auto-increment version number if not set
            if (! $version->version && $version->wa_template_id) {
                $maxVersion = static::where('wa_template_id', $version->wa_template_id)
                    ->max('version');
                $version->version = ($maxVersion ?? 0) + 1;
            }
        });
    }
}
