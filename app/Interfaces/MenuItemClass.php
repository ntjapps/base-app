<?php

namespace App\Interfaces;

use App\Traits\MenuItemConst;

class MenuItemClass
{
    use MenuItemConst;

    /**
     * Get current route expanded keys
     */    
    public static function currentRouteExpandedKeys(string $getName): ?string
    {
        /** Case for route name */
        switch ($getName) {
            default:
                $expandedKeys = null;
                break;
        }

        return $expandedKeys;
    }
}
