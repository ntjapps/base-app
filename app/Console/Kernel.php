<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Get the timezone that should be used by default for scheduled events.
     * Don't use timezone for DST time, use UTC instead
     */
    protected function scheduleTimezone(): \DateTimeZone|string|null
    {
        return 'Asia/Jakarta';
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /** Packages Cron */
        $schedule->command('horizon:snapshot')->everyFiveMinutes()->runInBackground()->withoutOverlapping();
        $schedule->command('storage:link')->everyFiveMinutes()->runInBackground()->withoutOverlapping();
        $schedule->command('model:prune')->hourly()->runInBackground()->withoutOverlapping();
        $schedule->command('queue:prune-failed')->hourly()->runInBackground()->withoutOverlapping();
        $schedule->command('queue:flush')->hourly()->runInBackground()->withoutOverlapping();
        $schedule->command('passport:purge')->hourly()->runInBackground()->withoutOverlapping();
        $schedule->command('penant:clear')->daily()->runInBackground()->withoutOverlapping();

        if ($this->app->environment('local')) {
            $schedule->command('telescope:prune')->hourly()->runInBackground()->withoutOverlapping();
        }

        /** Custom Jobs Cron */
        $schedule->job(new \App\Jobs\PruneLogDebugLevelJob)->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
