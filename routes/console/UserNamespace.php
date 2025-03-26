<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

Artisan::command('user:create {username} {password?}', function () {
    $userExists = User::withTrashed()->where('username', $this->argument('username'))->exists();
    if ($userExists) {
        return $this->info('Username: '.$this->argument('username').' already exists');
    }

    $password = is_null($this->argument('password')) ? config('auth.defaults.reset_password_data') : $this->argument('password');

    User::create([
        'username' => $this->argument('username'),
        'password' => Hash::make($password),
    ]);

    $this->info('Created user with username: '.$this->argument('username'));
    Log::alert('Console user:create executed', ['username' => $this->argument('username')]);
})->purpose('Create new user with password');

Artisan::command('user:delete {username}', function () {
    $userExists = User::where('username', $this->argument('username'))->exists();
    if ($userExists) {
        return $this->info('Username: '.$this->argument('username').' not found / already deleted');
    }

    User::where('username', $this->argument('username'))->first()?->delete();

    $this->info('Deleted user with username: '.$this->argument('username'));
    Log::alert('Console user:delete executed', ['username' => $this->argument('username')]);
})->purpose('Delete user');

Artisan::command('user:reset {username}', function () {
    $userExists = User::where('username', $this->argument('username'))->exists();
    if (! $userExists) {
        return $this->info('Username: '.$this->argument('username').' not found');
    }

    $user = User::where('username', $this->argument('username'))->first();
    $user->password = Hash::make(config('auth.defaults.reset_password_data'));
    $user->save();

    $this->info('Reset password for user with username: '.$this->argument('username').' and default password: '.config('auth.defaults.reset_password_data'));
    Log::alert('Console user:reset executed', ['username' => $this->argument('username')]);
})->purpose('Reset password for user');

Artisan::command('user:login {username} {password}', function () {
    $userExists = User::where('username', $this->argument('username'))->exists();
    if (! $userExists) {
        return $this->info('Username: '.$this->argument('username').' not found');
    }

    $user = User::where('username', $this->argument('username'))->first();
    $hashCheck = Hash::check($this->argument('password'), $user->password);

    $this->info('Check password for user with username: '.$this->argument('username').' and password: '.($hashCheck ? 'true' : 'false'));
    Log::alert('Console user:login executed', ['username' => $this->argument('username')]);
})->purpose('Check password for user');

Artisan::command('user:restore {username}', function () {
    $userExists = User::onlyTrashed()->where('username', $this->argument('username'))->exists();
    if ($userExists) {
        return $this->info('Username: '.$this->argument('username').' not trashed');
    }

    User::withTrashed()->where('username', $this->argument('username'))->first()->restore();

    $this->info('Restored user with username: '.$this->argument('username'));
    Log::alert('Console user:restore executed', ['username' => $this->argument('username')]);
})->purpose('Restore user');

Artisan::command('user:perm:grant {username} {permission}', function () {
    $user = User::where('username', $this->argument('username'))->first();

    if ($user === null) {
        return $this->info('User not found');
    }

    $user->givePermissionTo($this->argument('permission'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->info('Granted permission '.$this->argument('permission').' to user '.$this->argument('username'));
    Log::alert('Console user:grant executed', ['username' => $this->argument('username'), 'permission' => $this->argument('permission')]);
})->purpose('Grant direct permission for given user');

Artisan::command('user:perm:revoke {username} {permission}', function () {
    $user = User::where('username', $this->argument('username'))->first();

    if ($user === null) {
        return $this->info('User not found');
    }

    $user->revokePermissionTo($this->argument('permission'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->info('Revoked permission '.$this->argument('permission').' from user '.$this->argument('username'));

    Log::alert('Console user:revoke executed', ['username' => $this->argument('username'), 'permission' => $this->argument('permission')]);
})->purpose('Revoke direct permission for given user');

Artisan::command('user:role:grant {username} {role}', function () {
    $user = User::where('username', $this->argument('username'))->first();

    if ($user === null) {
        return $this->info('User not found');
    }

    $user->assignRole($this->argument('role'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->info('Granted role '.$this->argument('role').' to user '.$this->argument('username'));

    Log::alert('Console user:grant executed', ['username' => $this->argument('username'), 'role' => $this->argument('role')]);
})->purpose('Grant roles for given user');

Artisan::command('user:role:revoke {username} {role}', function () {
    $user = User::where('username', $this->argument('username'))->first();

    if ($user === null) {
        return $this->info('User not found');
    }

    $user->removeRole($this->argument('role'));

    /** Reset cached roles and permissions */
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    $this->info('Revoked role '.$this->argument('role').' from user '.$this->argument('username'));

    Log::alert('Console user:revoke executed', ['username' => $this->argument('username'), 'role' => $this->argument('role')]);
})->purpose('Revoke role for given user');
