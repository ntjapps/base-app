<?php

use App\Jobs\PruneLogDebugLevelJob;
use Illuminate\Support\Facades\Schedule;

/** Packages Cron */
Schedule::command('model:prune')->everyMinute();
Schedule::command('queue:prune-failed')->everyMinute();
Schedule::command('queue:prune-batches', ['--hours' => 24, '--unfinished' => 48, '--cancelled' => 48])->everyMinute();
Schedule::command('queue:flush')->everyMinute();

if (class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
    Schedule::command('horizon:snapshot')->everyFiveMinutes();
}

if (class_exists(\Laravel\Passport\PassportServiceProvider::class)) {
    Schedule::command('passport:purge')->everyMinute();
}

if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune')->everyFifteenMinutes();
}

if (class_exists(\Laravel\Pulse\PulseServiceProvider::class)) {
    Schedule::command('pulse:check')->dailyAt('00:00')->withoutOverlapping();
    Schedule::command('pulse:work')->everyFifteenMinutes()->withoutOverlapping();
    Schedule::command('pulse:clear', ['--type=cpu,memory,system'])->everyFifteenMinutes();
}

if (config('cache.default') === 'redis') {
    Schedule::command('cache:prune-stale-tags')->everyMinute();
}

/** Custom Jobs Cron */
Schedule::job(new PruneLogDebugLevelJob)->everyMinute();
