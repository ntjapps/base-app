<?php

use App\Models\Passport\Client;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;

Artisan::command('passport:client:list', function () {
    $clients = Client::all();
    $this->table([
        'ID', 'Name', 'Redirect', 'Password Client', 'Revoked', 'Created At', 'Updated At'
    ], $clients->map(function ($client) {
        return [
            $client->id,
            $client->name,
            $client->redirect,
            $client->password_client ? 'Yes' : 'No',
            $client->revoked ? 'Yes' : 'No',
            $client->created_at,
            $client->updated_at,
        ];
    }));
    Log::debug('Console passport:client:list executed', ['appName' => config('app.name')]);
})->purpose('List passport clients');

Artisan::command('passport:client:revoke {id}', function () {
    $client = Client::where('id', $this->argument('id'))->first();
    if ($client !== null) {
        $client->revoked = true;
        $client->save();
        $this->info('Client revoked');
        Log::alert('Console passport:client:revoke executed', ['appName' => config('app.name')]);
    } else {
        $this->info('Client not found');
    }
})->purpose('Revoke passport client');

Artisan::command('passport:client:unrevoke {id}', function () {
    $client = Client::where('id', $this->argument('id'))->first();
    if ($client !== null) {
        $client->revoked = false;
        $client->save();
        $this->info('Client unrevoked');
        Log::alert('Console passport:client:unrevoke executed', ['appName' => config('app.name')]);
    } else {
        $this->info('Client not found');
    }
})->purpose('Unrevoke passport client');

Artisan::command('passport:client:delete {id}', function () {
    $client = Client::where('id', $this->argument('id'))->first();
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

    $Client = Client::where('name', 'Personal Access Client Env')->first();
    if (! is_null($Client)) {
        $Client->delete();
    }

    $client = collect();

    DB::transaction(function () use (&$client) {
        $client = (new ClientRepository)->createPersonalAccessGrantClient('Personal Access Client Env');
        $client->id = config('passport.personal_access_client.id');
        $client->secret = config('passport.personal_access_client.secret', Str::random(40));
        $client->save();
    });

    $this->info('Client id: '.$client?->id);
    $this->info('Client Secret: '.$client?->secret);
    $this->info('Client id and secret generated from .env');

    Log::debug('Console passport:client:env executed', ['appName' => config('app.name')]);
})->purpose('Generate personal access client from .env');

Artisan::command('passport:client:grant:env', function () {
    if (config('passport.client_credentials_grant_client.id') === null) {
        $this->error('Please set PASSPORT_CLIENT_CREDENTIALS_GRANT_CLIENT_ID in .env');
        return;
    }

    $Client = Client::where('name', 'Client Credentials Client Env')->first();
    if (! is_null($Client)) {
        $Client->delete();
    }

    $client = collect();

    DB::transaction(function () use (&$client) {
        $client = (new ClientRepository)->createClientCredentialsGrantClient('Client Credentials Client Env');
        $client->id = config('passport.client_credentials_grant_client.id');
        $client->secret = config('passport.client_credentials_grant_client.secret', Str::random(40));
        $client->save();
    });

    $this->info('Client id: '.$client?->id);
    $this->info('Client secret: '.$client?->secret);
    $this->info('Client id and secret generated from .env');

    Log::debug('Console passport:client:grant:env executed', ['appName' => config('app.name')]);
})->purpose('Generate client credentials access client from .env');

Artisan::command('passport:client:rabbitmq:env', function () {
    if (config('passport.client_credentials_rabbitmq_client.id') === null) {
        $this->error('Please set RABBITMQ_CLIENT_CREDENTIALS_CLIENT_ID in .env');
        return;
    }

    $Client = Client::where('name', 'Rabbitmq Client Env')->first();
    if (! is_null($Client)) {
        $Client->delete();
    }

    $client = collect();

    DB::transaction(function () use (&$client) {
        $client = (new ClientRepository)->createClientCredentialsGrantClient('Rabbitmq Client Env');
        $client->id = config('passport.client_credentials_rabbitmq_client.id');
        $client->secret = config('passport.client_credentials_rabbitmq_client.secret', Str::random(40));
        $client->save();
    });

    $this->info('Client id: '.$client?->id);
    $this->info('Client secret: '.$client?->secret);
    $this->info('Client id and secret generated from .env');

    Log::debug('Console passport:client:rabbitmq:env executed', ['appName' => config('app.name')]);
})->purpose('Generate client credentials access client from .env');
