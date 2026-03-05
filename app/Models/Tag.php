<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
        'color',
        'enabled',
        'is_system',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * System tags that cannot be deleted.
     */
    public const SYSTEM_TAGS = [
        'human-handoff',
    ];
}
