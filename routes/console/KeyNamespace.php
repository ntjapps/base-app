<?php

use App\Jobs\KeyRotationJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('key:rotation', function () {
    KeyRotationJob::dispatch();

    $this->info('Key rotation dispatched');

    Log::alert('Console key:rotation executed', ['appName' => config('app.name')]);
})->purpose('Rotate key for encrypting/decrypting data');
