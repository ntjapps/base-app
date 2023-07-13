<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixUserPermGrantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:perm:grant {username} {permission}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant direct permission for given user.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('username', $this->argument('username'))->first();

        if ($user === null) {
            return $this->info('User not found');
        }

        $user->givePermissionTo($this->argument('permission'));

        /** Reset cached roles and permissions */
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Granted permission '.$this->argument('permission').' to user '.$this->argument('username'));
        Log::alert('Console user:grant executed', ['username' => $this->argument('username'), 'permission' => $this->argument('permission')]);
    }
}
