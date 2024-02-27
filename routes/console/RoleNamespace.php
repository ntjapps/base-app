<?php

use App\Models\Role;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Feature;

Artisan::command('role:list', function () {
    $this->info(Role::all()->pluck('name'));

    Log::info('Console role:list executed');
})->purpose('List all roles');

Artisan::command('role:grant {role} {permission}', function () {
    Role::find(Role::where('name', $this->argument('role'))->first()->id)->givePermissionTo($this->argument('permission'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Feature::flushCache();
    Log::alert('Console role:grant executed', ['role' => $this->argument('role'), 'permission' => $this->argument('permission')]);
})->purpose('Grant permission for given role');

Artisan::command('role:revoke {role} {permission}', function () {
    Role::find(Role::where('name', $this->argument('role'))->first()->id)->revokePermissionTo($this->argument('permission'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    Feature::flushCache();
    Log::alert('Console role:revoke executed', ['role' => $this->argument('role'), 'permission' => $this->argument('permission')]);
})->purpose('Revoke permission for given role');
