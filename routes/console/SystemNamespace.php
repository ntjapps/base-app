<?php

use App\Interfaces\InterfaceClass;
use App\Jobs\RolePermissionSyncJob;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Pennant\Feature;

Artisan::command('system:refresh', function () {
    $this->call('passport:client:env');
    $this->call('passport:client:rabbitmq:env');
    $this->info('Passport client generated');

    Redis::connection('cache')->flushdb(); /** Cache Database */
    Redis::connection('cachejob')->flushdb(); /** Job Database */
    Cache::flush(); /** Cache */
    InterfaceClass::flushRolePermissionCache();

    RolePermissionSyncJob::dispatchSync();

    Redis::connection('default')->flushdb(); /** Session Database */
    $this->info('All horizon cleared');

    // Telescope removed

    $this->call('cache:clear');
    if (config('pennant.default') === 'database') {
        Feature::flushCache();
        Feature::purge();
    }

    $this->call('view:clear');
    $this->info('Cache cleared');

    $this->info('System refreshed');

    Log::alert('Console system:refresh executed', ['appName' => config('app.name')]);
})->purpose('Refresh system');

Artisan::command('system:start', function () {
    if (! App::environment('local')) {
        $this->call('migrate', ['--force' => true]);
        $this->info('Migrated');
    }

    // Telescope removed

    $this->call('passport:client:env');
    $this->call('passport:client:rabbitmq:env');
    $this->info('Passport client generated');

    $this->call('cache:clear');
    if (config('pennant.default') === 'database') {
        Feature::flushCache();
        Feature::purge();
    }

    $this->call('storage:link');

    Redis::connection('cache')->flushdb(); /** Cache Database */
    Redis::connection('cachejob')->flushdb(); /** Job Database */
    Cache::flush(); /** Cache */
    InterfaceClass::flushRolePermissionCache();
    $this->call('view:clear');
    $this->info('Cache cleared');

    RolePermissionSyncJob::dispatchSync();

    $this->info('System startup scripts executed');

    Log::alert('Console system:start executed', ['appName' => config('app.name'), 'appVersion' => InterfaceClass::readApplicationVersion()]);
})->purpose('Start system');
