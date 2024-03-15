<?php

use App\Jobs\PruneLogDebugLevelJob;
use Illuminate\Support\Facades\Schedule;

/** Packages Cron */
Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('model:prune')->everyMinute()->withoutOverlapping();
Schedule::command('queue:prune-failed')->hourly()->withoutOverlapping();
Schedule::command('queue:prune-batches')->hourly()->withoutOverlapping();
Schedule::command('queue:flush')->hourly()->withoutOverlapping();
Schedule::command('passport:purge')->hourly()->withoutOverlapping();

if (config('cache.default') === 'redis') {
    Schedule::command('cache:prune-stale-tags')->hourly()->withoutOverlapping();
}

if (app()->environment('local')) {
    Schedule::command('telescope:prune')->hourly()->withoutOverlapping();
}

/** Custom Jobs Cron */
Schedule::job(new PruneLogDebugLevelJob)->dailyAt('00:00');
