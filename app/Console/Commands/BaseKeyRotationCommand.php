<?php

namespace App\Console\Commands;

use App\Jobs\KeyRotationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BaseKeyRotationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'key:rotation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate key for encrypting/decrypting data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        KeyRotationJob::dispatch();

        $this->info('Key rotation dispatched');

        Log::alert('Console key:rotation executed', ['appName' => config('app.name')]);
    }
}
