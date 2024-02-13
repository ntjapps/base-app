<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BaseQueueClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:clear:all';

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
        $this->call('queue:clear', ['--queue' => 'default']);
        $this->call('queue:clear', ['--queue' => 'long-run']);
        $this->call('queue:flush');
        Cache::flush();

        $this->info('All queue cleared');

        Log::alert('Console queue:clear:all executed', ['appName' => config('app.name')]);
    }
}
