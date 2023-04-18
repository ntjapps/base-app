<?php

namespace App\Interfaces;

use App\Traits\CommonFunction;

class InterfaceClass implements CacheKeyConst, PermissionConst, ResetPassConst
{
    use CommonFunction;
}
