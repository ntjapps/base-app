<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BaseUserResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password for user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userExists = User::where('username', $this->argument('username'))->exists();
        if (! $userExists) {
            return $this->info('Username: '.$this->argument('username').' not found');
        }

        $user = User::where('username', $this->argument('username'))->first();
        $user->password = Hash::make(config('auth.reset_password_data'));
        $user->save();

        $this->info('Reset password for user with username: '.$this->argument('username'));
        Log::alert('Console user:reset executed', ['username' => $this->argument('username')]);
    }
}
