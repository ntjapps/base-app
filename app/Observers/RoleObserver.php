<?php

namespace App\Observers;

use App\Interfaces\InterfaceClass;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class RoleObserver
{
    /**
     * Handle the Role "created" event.
     */
    public function created(Role $role): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Role "updated" event.
     */
    public function updated(Role $role): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Role "deleted" event.
     */
    public function deleted(Role $role): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Role "restored" event.
     */
    public function restored(Role $role): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }

    /**
     * Handle the Role "force deleted" event.
     */
    public function forceDeleted(Role $role): void
    {
        Cache::tags([InterfaceClass::MSTPERM])->flush();
        Feature::flushCache();
        Feature::purge();
    }
}
