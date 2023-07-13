<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixUserPermRevokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:perm:revoke {username} {permission}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke direct permission for given user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('username', $this->argument('username'))->first();

        if ($user === null) {
            return $this->info('User not found');
        }

        $user->revokePermissionTo($this->argument('permission'));

        /** Reset cached roles and permissions */
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Revoked permission '.$this->argument('permission').' from user '.$this->argument('username'));

        Log::alert('Console user:revoke executed', ['username' => $this->argument('username'), 'permission' => $this->argument('permission')]);
    }
}
