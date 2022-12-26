<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Artisan;

class MigrationStartListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        Artisan::call('down');
    }
}
