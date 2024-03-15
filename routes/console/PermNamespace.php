<?php

use App\Models\Permission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('perm:list', function () {
    $this->info(Permission::all()->pluck('name'));

    Log::info('Console perm:list executed');
})->purpose('List all permission');
