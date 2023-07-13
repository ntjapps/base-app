<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BaseUserRestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:restore {username}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userExists = User::onlyTrashed()->where('username', $this->argument('username'))->exists();
        if ($userExists) {
            return $this->info('Username: '.$this->argument('username').' not trashed');
        }

        User::withTrashed()->where('username', $this->argument('username'))->first()->restore();

        $this->info('Restored user with username: '.$this->argument('username'));
        Log::alert('Console user:restore executed', ['username' => $this->argument('username')]);
    }
}
