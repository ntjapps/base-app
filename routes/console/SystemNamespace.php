<?php

use App\Interfaces\InterfaceClass;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Pennant\Feature;

Artisan::command('system:refresh', function () {
    $this->call('passport:client:env');
    $this->info('Passport client generated');

    Redis::connection('horizon')->flushdb();
    Redis::connection('cache')->flushdb();
    Redis::connection('cachejob')->flushdb();
    Redis::connection('default')->flushdb();
    Cache::flush();
    $this->info('All horizon cleared');

    if (App::environment('local')) {
        $this->call('telescope:prune', ['--hours' => 0]);
        $this->info('Telescope pruned');
    }

    $this->call('cache:clear');
    if (config('pennant.default') === 'database') {
        Feature::flushCache();
        Feature::purge();
    }

    $this->info('Cache cleared');

    $this->info('System refreshed');

    Log::alert('Console system:refresh executed', ['appName' => config('app.name')]);
})->purpose('Refresh system');

Artisan::command('system:start', function () {
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

    Redis::connection('cache')->flushdb();
    Redis::connection('cachejob')->flushdb();
    Redis::connection('default')->flushdb();
    Cache::flush();
    $this->info('Cache cleared');

    $this->info('System startup scripts executed');

    Log::alert('Console system:start executed', ['appName' => config('app.name'), 'appVersion' => InterfaceClass::readApplicationVersion()]);
})->purpose('Start system');
