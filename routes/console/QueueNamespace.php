<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

Artisan::command('queue:clear:all', function () {
    $this->call('queue:clear', ['--queue' => 'default']);
    $this->call('queue:clear', ['--queue' => 'long-run']);
    $this->call('queue:flush');
    Cache::flush();

    $this->info('All queue cleared');

    Log::alert('Console queue:clear:all executed', ['appName' => config('app.name')]);
})->purpose('Delete all of the jobs from all queues');
