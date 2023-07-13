<?php

namespace App\Console\Commands;

use App\Models\PassportClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;

class PassportClientEnvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:client:env';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate personal access client from .env';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PassportClient::where('name', 'Personal Access Client Env')->delete();

        $client = new ClientRepository();
        $client->createPersonalAccessClient(null, 'Personal Access Client Env', 'http://localhost');

        $dbClient = PassportClient::where('name', 'Personal Access Client Env')->first();
        $dbClient->id = config('passport.personal_access_client.id');
        $dbClient->secret = config('passport.personal_access_client.secret');
        $dbClient->save();

        $this->info('Client id: '.$dbClient->id);
        $this->info('Client id and secret generated from .env');

        Log::alert('Console passport:client:env executed', ['appName' => config('app.name')]);
    }
}
