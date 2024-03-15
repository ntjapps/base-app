<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

Artisan::command('tools:uuid:generate', function () {
    $this->info('Generated UUID: '.Str::uuid());
    $this->info('If you want to use this UUID in .env, please use the following format: PASSPORT_PERSONAL_ACCESS_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
    $this->info('Secret can be generated with password generator with 40 non special characters');
    Log::alert('Console uuid:generate executed', ['appName' => config('app.name')]);
})->purpose('Generate UUID Tools');
