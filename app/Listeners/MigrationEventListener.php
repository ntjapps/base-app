<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class MigrationEventListener
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
        Log::channel('stack_migration')->alert('Migration run on application', ['appName' => config('app.name')]);
        Artisan::call('up');
    }
}
