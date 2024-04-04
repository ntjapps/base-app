<?php

namespace App\Interfaces;

interface PermissionConst
{
    /**
     * List of user Permission
     */
    public const ALLPERM = [
        self::SUPER,
    ];

    public const PRIVILEGEPERM = [
        self::SUPER,
    ];

    public const SUPER = 'root';
}
