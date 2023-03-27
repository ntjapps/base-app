<?php

namespace App\Observers;

use App\Interfaces\InterfaceClass;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class PermissionObserver
{
    /**
     * Handle the Permission "created" event.
     */
    public function created(Permission $permission): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Permission "updated" event.
     */
    public function updated(Permission $permission): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Permission "deleted" event.
     */
    public function deleted(Permission $permission): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Permission "restored" event.
     */
    public function restored(Permission $permission): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Permission "force deleted" event.
     */
    public function forceDeleted(Permission $permission): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }
}
