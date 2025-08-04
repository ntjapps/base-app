<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeleryTaskmeta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'celery_taskmeta';

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
