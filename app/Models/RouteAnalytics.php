<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RouteAnalytics extends Model
{
    use HasUuids;

    protected $table = 'route_analytics';

    protected $fillable = [
        'method',
        'path',
        'route_name',
        'route_group',
        'status_code',
        'duration_ms',
        'user_id',
        'user_name',
        'ip',
        'user_agent',
        'is_authenticated',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'duration_ms' => 'integer',
        'is_authenticated' => 'boolean',
    ];
}
