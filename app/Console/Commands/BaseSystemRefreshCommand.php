<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Pennant\Feature;

class BaseSystemRefreshCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Artisan::call('passport:client:env');
        $this->info('Passport client generated');

        Redis::connection('horizon')->flushdb();
        Redis::connection('cache')->flushdb();
        Redis::connection('default')->flushdb();
        Cache::flush();
        $this->info('All horizon cleared');

        if (App::environment('local')) {
            Artisan::call('telescope:prune', ['--hours' => 0]);
            $this->info('Telescope pruned');
        }

        Artisan::call('cache:clear');
        if (config('pennant.default') === 'database') {
            Feature::flushCache();
            Feature::purge();
        }

        $this->info('Cache cleared');

        $this->info('System refreshed');

        Log::alert('Console system:refresh executed', ['appName' => config('app.name')]);
    }
}
