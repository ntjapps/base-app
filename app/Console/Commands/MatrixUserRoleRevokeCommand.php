<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixUserRoleRevokeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:role:revoke {username} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revoke role for given user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('username', $this->argument('username'))->first();

        if ($user === null) {
            return $this->info('User not found');
        }

        $user->removeRole($this->argument('role'));

        /** Reset cached roles and permissions */
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Revoked role '.$this->argument('role').' from user '.$this->argument('username'));

        Log::alert('Console user:revoke executed', ['username' => $this->argument('username'), 'role' => $this->argument('role')]);
    }
}
