<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /** Packages Cron */
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('storage:link')->everyFiveMinutes();
        $schedule->command('log:delete')->hourly();
        $schedule->command('queue:prune-failed')->daily();
        $schedule->command('queue:flush')->daily();
        $schedule->command('model:prune')->daily();
        $schedule->command('sanctum:prune-expired --hours=24')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
