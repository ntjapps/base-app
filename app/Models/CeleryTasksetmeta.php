<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeleryTasksetmeta extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'celery_tasksetmeta';

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
