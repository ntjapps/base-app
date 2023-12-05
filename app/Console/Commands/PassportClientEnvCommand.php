<?php

namespace App\Console\Commands;

use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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
        $passportClient = PassportClient::where('name', 'Personal Access Client Env')->first();
        if (! is_null($passportClient)) {
            PassportPersonalAccessClient::where('client_id', $passportClient->id)->delete();
            $passportClient->delete();
        }

        $client = new ClientRepository();
        $client->createPersonalAccessClient(null, 'Personal Access Client Env', 'http://localhost');

        $dbClient = PassportClient::where('name', 'Personal Access Client Env')->first();
        if (! is_null($dbClient)) {
            DB::transaction(function () use ($dbClient) {
                PassportPersonalAccessClient::where('client_id', $dbClient->id)->delete();

                $dbClient->id = config('passport.personal_access_client.id');
                $dbClient->secret = config('passport.personal_access_client.secret');
                $dbClient->save();

                PassportPersonalAccessClient::create([
                    'client_id' => $dbClient->id,
                ]);
            });
        } else {
            $this->error('Client not found');
        }

        $this->info('Client id: '.$dbClient->id);
        $this->info('Client id and secret generated from .env');

        Log::debug('Console passport:client:env executed', ['appName' => config('app.name')]);
    }
}
