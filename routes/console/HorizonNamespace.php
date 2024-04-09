<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

Artisan::command('horizon:clear:all', function () {
    Cache::flush();

    $this->call('horizon:clear');
    $this->call('horizon:clear', ['--queue' => 'long-run']);

    $this->info('All horizon cleared');

    Redis::connection('horizon_redis')->flushdb();

    $this->info('All horizon redis cleared');

    Log::alert('Console horizon:clear:all executed', ['appName' => config('app.name')]);
})->purpose('Delete all of the jobs from all queues');
