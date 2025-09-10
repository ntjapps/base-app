<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('midtrans:test', function () {
    $this->comment('Running Midtrans SNAP test...');

    $orderId = 'TEST-'.time();
    $amount = 10000; // sample amount in smallest currency unit
    $customer = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'phone' => '081234567890',
    ];
    $items = [
        [
            'id' => 'item-1',
            'price' => $amount,
            'quantity' => 1,
            'name' => 'Test Service',
        ],
    ];

    try {
        // anonymous class using the trait
        $client = new class
        {
            use \App\Traits\MidtransFunction;
        };

        $this->info("Creating SNAP transaction for order: {$orderId} amount: {$amount}");

        $response = $client->createSnapTransaction($orderId, $amount, $customer, $items);

        if (is_null($response)) {
            $this->warn('Midtrans is disabled in configuration or not enabled via MIDTRANS_ENABLED.');
            Log::warning('midtrans:test skipped - midtrans disabled', ['order_id' => $orderId]);

            return;
        }

        $this->info('Midtrans Response:');
        $this->line(is_array($response) ? json_encode($response, JSON_PRETTY_PRINT) : (string) $response);

        Log::info('midtrans:test executed', ['order_id' => $orderId, 'response' => $response]);
    } catch (\Exception $e) {
        $this->error('Error during Midtrans test: '.$e->getMessage());
        Log::error('midtrans:test error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
    }
})->purpose('Test Midtrans SNAP integration using MidtransFunction trait');
