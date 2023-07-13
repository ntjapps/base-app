<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaseHorizonClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'horizon:clear:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all of the jobs from all queues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Cache::flush();

        Artisan::call('horizon:clear');
        Artisan::call('horizon:clear', ['--queue' => 'long-run']);

        $this->info('All horizon cleared');

        Log::alert('Console horizon:clear:all executed', ['appName' => config('app.name')]);
    }
}
