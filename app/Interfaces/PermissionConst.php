<?php

namespace App\Interfaces;

interface PermissionConst
{
    /**
     * List of user Roles
     */
    public const ALLROLE = [
        self::SUPERROLE,
    ];

    public const SUPERROLE = 'SU';

    /**
     * List of user Permission
     */
    public const ALLPERM = [
        self::SUPER,
    ];

    public const SUPER = 'root';
}
