<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ToolsUuidGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tools-uuid-generate-command';

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
        $this->info('Generated UUID: '.Str::uuid());
        $this->info('If you want to use this UUID in .env, please use the following format: PASSPORT_PERSONAL_ACCESS_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
        $this->info('Secret can be generated with password generator with 40 non special characters');
        Log::alert('Console uuid:generate executed', ['appName' => config('app.name')]);
    }
}
