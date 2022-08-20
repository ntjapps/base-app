<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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

Artisan::command('role:super {phone}', function($phone) {
  User::find(User::where('phone', $phone)->first()->id)->syncRoles(User::SUPERROLE);
  Log::alert('Console role:super executed', ['phone' => $phone, 'role' => User::SUPERROLE]);
})->purpose('Set user with entered phone number as super user');

Artisan::command('role:grant {role} {permission}', function($role, $permission) {
  Role::find(Role::where('name', $role)->first()->id)->givePermissionTo($permission);
  Log::alert('Console role:grant executed', ['role' => $role, 'permission' => $permission]);
})->purpose('Grant permission for given role');

Artisan::command('role:revoke {role} {permission}', function($role, $permission) {
  Role::find(Role::where('name', $role)->first()->id)->revokePermissionTo($permission);
  Log::alert('Console role:revoke executed', ['role' => $role, 'permission' => $permission]);
})->purpose('Revoke permission for given role');

Artisan::command('role:clear {phone}', function($phone) {
  User::find(User::where('phone', $phone)->first()->id)->syncRoles([]);
  Log::alert('Console role:clear executed', ['phone' => $phone]);
})->purpose('Clear all role for given user');

Artisan::command('perm:clear {phone}', function($phone) {
  User::find(User::where('phone', $phone)->first()->id)->syncPermissions([]);
  Log::alert('Console perm:clear executed', ['phone' => $phone]);
})->purpose('Clear all permission for given user');

Artisan::command('user:clear {phone}', function($phone) {
  User::find(User::where('phone', $phone)->first()->id)->syncRoles([]);
  User::find(User::where('phone', $phone)->first()->id)->syncPermissions([]);
  Log::alert('Console user:clear executed', ['phone' => $phone]);
})->purpose('Clear all roles & permission for given user');

Artisan::command('unit:test', function() {
    /** NULL */
})->purpose('Test Query / Any Test');

Artisan::command('patch:deploy', function() {
  /** PATCH DO HERE */
  $patch = 'NULL';

  /** Alert Log for patch deployment and clear application cache */
  Artisan::call('cache:clear');
  Artisan::call('up');
  Log::alert('Deployed patch', ['patchId' => $patch, 'appName' => config('app.name')]);
})->purpose('Deploy patch');
