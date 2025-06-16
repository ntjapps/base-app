<?php

use App\Jobs\PruneLogDebugLevelJob;
use Illuminate\Support\Facades\Schedule;

/** Packages Cron */
Schedule::command('model:prune')->everyMinute();
Schedule::command('queue:prune-failed')->everyMinute();
Schedule::command('queue:prune-batches', ['--hours' => 24, '--unfinished' => 48, '--cancelled' => 48])->everyMinute();
Schedule::command('queue:flush')->everyMinute();

if (class_exists(\Laravel\Passport\PassportServiceProvider::class)) {
    Schedule::command('passport:purge')->everyMinute();
}

if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune')->everyFifteenMinutes();
}

if (config('cache.default') === 'redis') {
    Schedule::command('cache:prune-stale-tags')->everyMinute();
}

/** Custom Jobs Cron */
Schedule::job(new PruneLogDebugLevelJob)->hourly();
