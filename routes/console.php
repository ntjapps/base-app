<?php

use App\Models\PassportClient;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Laravel\Pennant\Feature;
use Laravel\Telescope\Telescope;
use OTPHP\TOTP;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('system:refresh', function () {
    Artisan::call('passport:client:env');
    Artisan::call('passport:clientgrant:env');
    Artisan::call('horizon:clear:all');
    Artisan::call('pennant:clear');
    Artisan::call('cache:clear');
})->purpose('Refresh system');

Artisan::command('penant:clear', function () {
    Feature::flushCache();
    Feature::purge();
    $this->info('Penant cache cleared');
    Log::alert('Console penant:clear executed', ['appName' => config('app.name')]);
})->purpose('Clear penant cache');

Artisan::command('queue:clear:all', function () {
    Artisan::call('queue:clear', ['--queue' => 'default']);
    Artisan::call('queue:clear', ['--queue' => 'long-run']);
    Artisan::call('queue:flush');
    Cache::flush();
    $this->info('All queue cleared');
    Log::alert('Console queue:clear:all executed', ['appName' => config('app.name')]);
})->purpose('Delete all of the jobs from all queues');

Artisan::command('horizon:clear:all', function () {
    Redis::connection('horizon')->flushdb();
    Redis::connection('cache')->flushdb();
    Redis::connection('default')->flushdb();
    Cache::flush();
    $this->info('All horizon cleared');
    Log::alert('Console horizon:clear:all executed', ['appName' => config('app.name')]);
})->purpose('Delete all of the jobs from all queues');

Artisan::command('uuid:generate', function () {
    $this->info('Generated UUID: '.Str::uuid());
    $this->info('If you want to use this UUID in .env, please use the following format: PASSPORT_PERSONAL_ACCESS_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
    $this->info('Secret can be generated with password generator with 40 non special characters');
    Log::alert('Console uuid:generate executed', ['appName' => config('app.name')]);
})->purpose('Generate uuid');

Artisan::command('passport:client:env', function () {
    PassportClient::where('name', 'Personal Access Client Env')->delete();

    $client = new ClientRepository();
    $client->createPersonalAccessClient(null, 'Personal Access Client Env', 'http://localhost');

    $dbClient = PassportClient::where('name', 'Personal Access Client Env')->first();
    $dbClient->id = config('passport.personal_access_client.id');
    $dbClient->secret = config('passport.personal_access_client.secret');
    $dbClient->save();

    $this->info('Client id: '.$dbClient->id);
    $this->info('Client id and secret generated from .env');
    Log::alert('Console passport:client:env executed', ['appName' => config('app.name')]);
})->purpose('Generate personal access client from .env');

Artisan::command('passport:clientgrant:env', function () {
    PassportClient::where('name', 'Client Credentials Client Env')->delete();

    $client = new ClientRepository();
    $client->create(null, 'Client Credentials Client Env', '');

    $dbClient = PassportClient::where('name', 'Client Credentials Client Env')->first();
    $dbClient->id = config('passport.client_credentials_grant_client.id');
    $dbClient->secret = config('passport.client_credentials_grant_client.secret');
    $dbClient->save();

    $this->info('Client id: '.$dbClient->id);
    $this->info('Client id and secret generated from .env');
    Log::alert('Console passport:clientgrant:env executed', ['appName' => config('app.name')]);
})->purpose('Generate client credentials client from .env');

Artisan::command('passport:client:delete {id}', function ($id) {
    $client = PassportClient::where('id', $id)->first();
    if ($client !== null) {
        $client->delete();
        $this->info('Client deleted');
        Log::alert('Console passport:client:delete executed', ['appName' => config('app.name')]);
    } else {
        $this->info('Client not found');
    }
})->purpose('Delete passport client');

Artisan::command('role:list', function () {
    $this->info(Role::all()->pluck('name'));
    Log::info('Console role:list executed');
})->purpose('List all roles');

Artisan::command('perm:list', function () {
    $this->info(Permission::all()->pluck('name'));
    Log::info('Console perm:list executed');
})->purpose('List all permission');

Artisan::command('role:grant {role} {permission}', function ($role, $permission) {
    Role::find(Role::where('name', $role)->first()->id)->givePermissionTo($permission);
    Feature::flushCache();
    Feature::purge();
    Log::alert('Console role:grant executed', ['role' => $role, 'permission' => $permission]);
})->purpose('Grant permission for given role');

Artisan::command('role:revoke {role} {permission}', function ($role, $permission) {
    Role::find(Role::where('name', $role)->first()->id)->revokePermissionTo($permission);
    Feature::flushCache();
    Feature::purge();
    Log::alert('Console role:revoke executed', ['role' => $role, 'permission' => $permission]);
})->purpose('Revoke permission for given role');

Artisan::command('user:create {username} {password}', function ($username, $password) {
    $user_count = User::where('username', $username)->count();
    if ($user_count != 0) {
        return $this->info('Username: '.$username.' already exists');
    }

    User::create([
        'username' => $username,
        'password' => \Illuminate\Support\Facades\Hash::make($password),
    ]);
    $this->info('Created user with username: '.$username);
    Log::alert('Console user:create executed', ['username' => $username]);
})->purpose('Create new user with password');

Artisan::command('user:reset {username}', function ($username) {
    $user_count = User::where('username', $username)->count();
    if ($user_count != 0) {
        $user = User::where('username', $username)->first();
        $user->password = Hash::make('login');
        $user->save();
        $this->info('Reset user with username: '.$username);
    }
    $this->info('Command reset executed');
    Log::alert('Console user:reset executed', ['username' => $username]);
})->purpose('Reset user with password');

Artisan::command('user:totp {username} {secret?}', function ($username, $secret = null) {
    $user_count = User::where('username', $username)->count();
    if ($user_count != 0) {
        return $this->info('Username: '.$username.' already exists');
    }

    (string) $totp_key = TOTP::create($secret)->getSecret();
    User::create([
        'username' => $username,
        'totp_key' => $totp_key,
    ]);
    $this->info('Created user with username: '.$username.' and TOTP key: '.$totp_key);
    Log::alert('Console user:create executed', ['username' => $username]);
})->purpose('Create new user with TOTP');

Artisan::command('user:role:grant {username} {role}', function ($username, $role) {
    $user = User::where('username', $username)->first();

    if ($user == null) {
        return $this->info('User not found');
    }

    $user->assignRole($role);
    Feature::flushCache();
    Feature::purge();
    $this->info('Granted role '.$role.' to user '.$username);
    Log::alert('Console user:grant executed', ['username' => $username, 'role' => $role]);
})->purpose('Grant role for given user');

Artisan::command('user:role:revoke {username} {role}', function ($username, $role) {
    $user = User::where('username', $username)->first();

    if ($user == null) {
        return $this->info('User not found');
    }

    $user->removeRole($role);
    Feature::flushCache();
    Feature::purge();
    $this->info('Revoked role '.$role.' from user '.$username);
    Log::alert('Console user:revoke executed', ['username' => $username, 'role' => $role]);
})->purpose('Revoke role for given user');

Artisan::command('user:perm:grant {username} {permission}', function ($username, $permission) {
    $user = User::where('username', $username)->first();

    if ($user == null) {
        return $this->info('User not found');
    }

    $user->givePermissionTo($permission);
    Feature::flushCache();
    Feature::purge();
    $this->info('Granted permission '.$permission.' to user '.$username);
    Log::alert('Console user:grant executed', ['username' => $username, 'permission' => $permission]);
})->purpose('Grant direct permission for given user');

Artisan::command('user:perm:revoke {username} {permission}', function ($username, $permission) {
    $user = User::where('username', $username)->first();

    if ($user == null) {
        return $this->info('User not found');
    }

    $user->revokePermissionTo($permission);
    Feature::flushCache();
    Feature::purge();
    $this->info('Revoked permission '.$permission.' from user '.$username);
    Log::alert('Console user:revoke executed', ['username' => $username, 'permission' => $permission]);
})->purpose('Revoke direct permission for given user');

Artisan::command('telegram:test', function () {
    $auth = \Illuminate\Support\Facades\Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/getMe');

    if ($auth->successful()) {
        $this->info('Telegram Bot is connected');

        $message = 'Test Telegram Bot';

        $response = \Illuminate\Support\Facades\Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/sendMessage', [
            'chat_id' => config('telegram.group_id'),
            'text' => substr($message, 0, 4096),
        ]);

        if ($response->successful()) {
            $this->info('Telegram Bot sent message');
        } else {
            $this->info('Telegram Bot failed to send message');
        }
    } else {
        $this->info('Telegram Bot is not connected');
    }
    Log::alert('Console telegram:test executed', ['appName' => config('app.name')]);
})->purpose('Test Telegram Bot');

Artisan::command('error:test', function () {
    throw new \App\Exceptions\CommonCustomException('Test Error');
    Log::alert('Console unit:test executed', ['appName' => config('app.name')]);
})->purpose('Test Common Custom Exception');

Artisan::command('unit:test', function () {
    /** NULL */
    Log::alert('Console unit:test executed', ['appName' => config('app.name')]);
})->purpose('Test Query / Any Test');

Artisan::command('patch:deploy', function () {
    /** Memory Leak mitigation */
    if (App::environment('local')) {
        Telescope::stopRecording();
    }

    Cache::flush();

    /** PATCH DO HERE */
    $patchId = 'NULL';

    if ($this->confirm('Are you sure you want to deploy patch '.$patchId.'?')) {
        $this->info('Deploying patch '.$patchId.'...');

        /** Alert Log for patch deployment and clear application cache */
        Cache::flush();
        Feature::flushCache();
        Artisan::call('up');
        Log::alert('Console patch:deploy executed', ['patchId' => $patchId, 'appName' => config('app.name')]);
    } else {
        $this->info('Deploying patch '.$patchId.' aborted');
    }
})->purpose('Deploy patch');
