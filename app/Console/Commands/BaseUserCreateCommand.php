<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class BaseUserCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create {username} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user with password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userExists = User::withTrashed()->where('username', $this->argument('username'))->exists();
        if ($userExists) {
            return $this->info('Username: '.$this->argument('username').' already exists');
        }

        $password = is_null($this->argument('password')) ? config('auth.reset_password_data') : $this->argument('password');

        User::create([
            'username' => $this->argument('username'),
            'password' => Hash::make($password),
        ]);

        $this->info('Created user with username: '.$this->argument('username'));
        Log::alert('Console user:create executed', ['username' => $this->argument('username')]);
    }
}
