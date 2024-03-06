<?php

use App\Models\PassportClient;
use App\Models\PassportPersonalAccessClient;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\ClientRepository;
use Illuminate\Support\Str;

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
    if (config('passport.personal_access_client.id') === null) {
        $this->error('Please set PASSPORT_PERSONAL_ACCESS_CLIENT_ID in .env');
        return;
    }
    
    $passportClient = PassportClient::where('name', 'Personal Access Client Env')->first();
    if (! is_null($passportClient)) {
        PassportPersonalAccessClient::where('client_id', $passportClient->id)->delete();
        $passportClient->delete();
    }

    $client = collect();

    DB::transaction(function () use (&$client) {
        $client = (new ClientRepository)->createPersonalAccessClient(null, 'Personal Access Client Env', 'http://localhost');

        $client->id = config('passport.personal_access_client.id');
        $client->secret = config('passport.personal_access_client.secret', Str::random(40));
        $client->save();
    });

    $this->info('Client id: '.$client?->id);
    $this->info('Client Secret: '.$client?->secret);
    $this->info('Client id and secret generated from .env');

    Log::debug('Console passport:client:env executed', ['appName' => config('app.name')]);
})->purpose('Generate personal access client from .env');

Artisan::command('passport:clientgrant:env', function () {
    if (config('passport.client_credentials_grant_client.id') === null) {
        $this->error('Please set PASSPORT_CLIENT_CREDENTIALS_GRANT_CLIENT_ID in .env');
        return;
    }

    $passportClient = PassportClient::where('name', 'Client Credentials Client Env')->first();
    if (! is_null($passportClient)) {
        PassportPersonalAccessClient::where('client_id', $passportClient->id)->delete();
        $passportClient->delete();
    }

    $client = collect();

    DB::transaction(function () use (&$client) {
        $client = (new ClientRepository)->create(null, 'Client Credentials Client Env', '');

        $client->id = config('passport.client_credentials_grant_client.id');
        $client->secret = config('passport.client_credentials_grant_client.secret', Str::random(40));
        $client->save();
    });

    $this->info('Client id: '.$client?->id);
    $this->info('Client secret: '.$client?->secret);
    $this->info('Client id and secret generated from .env');

    Log::debug('Console passport:client:env executed', ['appName' => config('app.name')]);
})->purpose('Generate personal access client from .env');
