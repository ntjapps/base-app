<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('ipaymu:test', function () {
    $this->comment('Running iPaymu payment test...');

    $referenceId = 'TEST-'.time();
    $body = [
        'product' => ['Test Product'],
        'qty' => ['1'],
        'price' => ['10000'],
        'description' => ['Test product'],
        'returnUrl' => 'https://example.com/return',
        'notifyUrl' => 'https://example.com/notify',
        'cancelUrl' => 'https://example.com/cancel',
        'referenceId' => $referenceId,
        'buyerName' => 'Test Buyer',
        'buyerEmail' => 'buyer@example.com',
        'buyerPhone' => '081234567890',
    ];

    try {
        $client = new class
        {
            use \App\Traits\IpaymuFunction;
        };

        $this->info("Creating iPaymu payment reference: {$referenceId}");

        $response = $client->createIpaymuPayment($body);

        if (is_null($response)) {
            $this->warn('iPaymu is disabled in configuration or not enabled via IPAYMU_ENABLED.');
            Log::warning('ipaymu:test skipped - ipaymu disabled', ['referenceId' => $referenceId]);

            return;
        }

        $this->info('iPaymu Response:');
        $this->line(is_array($response) ? json_encode($response, JSON_PRETTY_PRINT) : (string) $response);

        Log::info('ipaymu:test executed', ['referenceId' => $referenceId, 'response' => $response]);
    } catch (\Exception $e) {
        $this->error('Error during iPaymu test: '.$e->getMessage());
        Log::error('ipaymu:test error', ['referenceId' => $referenceId, 'error' => $e->getMessage()]);
    }
})->purpose('Test iPaymu payment integration using IpaymuFunction trait');
