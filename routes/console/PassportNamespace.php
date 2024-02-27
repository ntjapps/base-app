<?php

use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;

Artisan::command('passport:client:delete {id}', function () {
    $client = PassportClient::where('id', $this->argument('id'))->first();
    if ($client !== null) {
        $client->delete();
        $this->info('Client deleted');
        Log::alert('Console passport:client:delete executed', ['appName' => config('app.name')]);
    } else {
        $this->info('Client not found');
    }
})->purpose('Delete passport client');

Artisan::command('passport:client:env', function () {
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
})->purpose('Generate personal access client from .env');
