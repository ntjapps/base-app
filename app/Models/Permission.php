<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasUuids;

    protected string $guard_name = 'web';

    public const GUARD_NAME = 'web';

    /**
     * The attributes that are NOT mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<int, string>
     */
    protected $fillable = [
        'name',
        'guard_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<int, string>
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast.
     *
     * @var list<string, string>
     */
    protected $casts = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];
}
