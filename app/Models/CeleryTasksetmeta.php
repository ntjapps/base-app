<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;

class CeleryTasksetmeta extends Model
{
    use Prunable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'celery_tasksetmeta';

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        return static::where('date_done', '<=', now()->subDays(3));
    }

    /**
     * Column info:
     * id
     * taskset_id
     * result
     * date_done
     */

    /**
     * The attributes that are NOT mass assignable.
     *
     * @var array
     */
    protected $guarded = ['*'];
}
