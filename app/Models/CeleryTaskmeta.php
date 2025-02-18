<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable as Prunable;
use Illuminate\Database\Eloquent\Model;

class CeleryTaskmeta extends Model
{
    use Prunable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'celery_taskmeta';

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
     * task_id
     * status
     * result
     * date_done
     * traceback
     * name
     * args
     * kwargs
     * worker
     * retries
     * queue
     */

    /**
     * The attributes that are NOT mass assignable.
     *
     * @var array
     */
    protected $guarded = ['*'];
}
