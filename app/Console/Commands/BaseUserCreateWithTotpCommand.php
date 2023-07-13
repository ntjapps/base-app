<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use OTPHP\TOTP;

class BaseUserCreateWithTotpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:totp {username} {secret?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user with TOTP.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userExists = User::where('username', $this->argument('username'))->exists();
        if ($userExists) {
            return $this->info('Username: '.$this->argument('username').' already exists');
        }

        (string) $totp_key = TOTP::create($this->argument('secret'))->getSecret();

        User::create([
            'username' => $this->argument('username'),
            'totp_key' => $totp_key,
        ]);

        $this->info('Created user with username: '.$this->argument('username').' and TOTP key: '.$totp_key);
        Log::alert('Console user:create executed', ['username' => $this->argument('username')]);
    }
}
