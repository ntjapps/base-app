<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatrixUserRoleGrantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:role:grant {username} {role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::where('username', $this->argument('username'))->first();

        if ($user === null) {
            return $this->info('User not found');
        }

        $user->assignRole($this->argument('role'));

        /** Reset cached roles and permissions */
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('Granted role '.$this->argument('role').' to user '.$this->argument('username'));

        Log::alert('Console user:grant executed', ['username' => $this->argument('username'), 'role' => $this->argument('role')]);
    }
}
