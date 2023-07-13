<?php

namespace App\Console\Commands;

use App\Models\PassportClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PassportClientDeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client:delete {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete passport client';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = PassportClient::where('id', $this->argument('id'))->first();
        if ($client !== null) {
            $client->delete();
            $this->info('Client deleted');
            Log::alert('Console passport:client:delete executed', ['appName' => config('app.name')]);
        } else {
            $this->info('Client not found');
        }
    }
}
