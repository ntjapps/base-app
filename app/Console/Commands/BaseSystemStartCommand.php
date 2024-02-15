<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

class BaseSystemStartCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! App::environment('local')) {
            $this->call('migrate', ['--force' => true]);
            $this->info('Migrated');
        }

        if (App::environment('local')) {
            $this->call('telescope:prune', ['--hours' => 0]);
            $this->info('Telescope pruned');
        }

        $this->call('passport:client:env');
        $this->info('Passport client generated');

        $this->call('cache:clear');
        if (config('pennant.default') === 'database') {
            Feature::flushCache();
            Feature::purge();
        }

        $this->call('storage:link');

        $this->info('Cache cleared');

        $this->info('System startup scripts executed');

        Log::alert('Console system:start executed', ['appName' => config('app.name')]);
    }
}
