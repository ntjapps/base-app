<?php

use App\Jobs\PruneLogDebugLevelJob;
use Illuminate\Support\Facades\Schedule;

/** Packages Cron */
Schedule::command('model:prune')->everyMinute()->withoutOverlapping();
Schedule::command('queue:prune-failed')->hourly()->withoutOverlapping();
Schedule::command('queue:prune-batches')->hourly()->withoutOverlapping();
Schedule::command('queue:flush')->hourly()->withoutOverlapping();

if (class_exists(\Laravel\Horizon\HorizonServiceProvider::class)) {
    Schedule::command('horizon:snapshot')->everyFiveMinutes()->withoutOverlapping();
}

if (class_exists(\Laravel\Passport\PassportServiceProvider::class)) {
    Schedule::command('passport:purge')->hourly()->withoutOverlapping();
}

if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
    Schedule::command('telescope:prune')->hourly()->withoutOverlapping();
}

if (class_exists(\Laravel\Pulse\PulseServiceProvider::class)) {
    Schedule::command('pulse:check', ['--once'])->everyThirtySeconds()->withoutOverlapping();
    Schedule::command('pulse:work', ['--stop-when-empty'])->everyTenSeconds()->withoutOverlapping(); /** Must be more frequent than pulse:check */
    Schedule::command('pulse:clear', ['--type=cpu,memory,system'])->hourly()->withoutOverlapping();
}

if (config('cache.default') === 'redis') {
    Schedule::command('cache:prune-stale-tags')->hourly()->withoutOverlapping();
}

/** Custom Jobs Cron */
Schedule::job(new PruneLogDebugLevelJob)->everyMinute();
