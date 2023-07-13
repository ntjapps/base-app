<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BaseUserDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:delete {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userExists = User::where('username', $this->argument('username'))->exists();
        if ($userExists) {
            return $this->info('Username: '.$this->argument('username').' not found / already deleted');
        }

        User::where('username', $this->argument('username'))->first()->delete();

        $this->info('Deleted user with username: '.$this->argument('username'));
        Log::alert('Console user:delete executed', ['username' => $this->argument('username')]);
    }
}
