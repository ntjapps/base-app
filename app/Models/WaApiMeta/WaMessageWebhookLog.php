<?php

namespace App\Models\WaApiMeta;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class WaMessageWebhookLog extends Model
{
    use HasUuids, Prunable;

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->subMonth());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<int, string>
     */
    protected $fillable = [
        'phone_number_id',
        'display_phone_number',
        'contact_wa_id',
        'contact_name',
        'message_id',
        'message_from',
        'message_type',
        'message_body',
        'timestamp',
        'raw_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var list<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',
    ];

    /**
     * Get the message thread that this webhook message belongs to.
     */
    public function thread(): MorphOne
    {
        return $this->morphOne(WaApiMessageThreads::class, 'messageable');
    }
}
