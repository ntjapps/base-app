<?php

namespace App\Traits;

use Carbon\Carbon;

trait CommonFunction
{
    /**
     * List of function defined constants
     */
    public static function getPassportTokenLifetime(): Carbon
    {
        return Carbon::now()->setTimezone('Asia/Jakarta')->startOfDay()->addDays(1)->setTimezone('UTC');
    }

    public static function getPassportAuthTokenLifetime(): Carbon
    {
        return Carbon::now()->setTimezone('Asia/Jakarta')->addMinutes(15)->setTimezone('UTC');
    }

    public static function getPassportRefreshTokenLifetime(): Carbon
    {
        return Carbon::now()->setTimezone('Asia/Jakarta')->startOfDay()->addDays(1)->setTimezone('UTC');
    }
}
