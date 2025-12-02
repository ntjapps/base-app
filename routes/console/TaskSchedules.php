<?php

use App\Jobs\PruneLogDebugLevelJob;
use App\Jobs\WaApiCleanOrphanedThreadLog;
use Illuminate\Support\Facades\Schedule;

/** Packages Cron */
Schedule::command('model:prune')->everyMinute();
Schedule::command('queue:prune-failed')->everyMinute();
Schedule::command('queue:prune-batches', ['--hours' => 24, '--unfinished' => 48, '--cancelled' => 48])->everyMinute();
Schedule::command('queue:flush')->everyMinute();

if (class_exists(\Laravel\Passport\PassportServiceProvider::class)) {
    Schedule::command('passport:purge')->everyMinute();
}

// Telescope removed

if (config('cache.default') === 'redis') {
    Schedule::command('cache:prune-stale-tags')->everyMinute();
}

/** Custom Jobs Cron */
Schedule::job(new PruneLogDebugLevelJob)->hourly();

// Clean up orphaned WhatsApp message threads every day
Schedule::job(new WaApiCleanOrphanedThreadLog)->hourly();
