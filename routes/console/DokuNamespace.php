<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Artisan::command('doku:test', function () {
    $this->comment('Running Doku Checkout test...');

    $invoiceNumber = 'TEST-'.time();
    $body = [
        'order' => [
            'amount' => 80000,
            'invoice_number' => substr('INV'.time(), 0, 30),
        ],
        'payment' => [
            'payment_due_date' => 60,
        ],
    ];

    try {
        $client = new class
        {
            use \App\Traits\DokuFunction;
        };

        $this->info("Creating Doku payment for invoice: {$invoiceNumber} amount: 10000");

        $response = $client->createDokuPayment($body);

        if (is_null($response)) {
            $this->warn('Doku is disabled in configuration or not enabled via DOKU_ENABLED.');
            Log::warning('doku:test skipped - doku disabled', ['invoice_number' => $invoiceNumber]);

            return;
        }

        $this->info('Doku Response:');
        $this->line(is_array($response) ? json_encode($response, JSON_PRETTY_PRINT) : (string) $response);

        Log::info('doku:test executed', ['invoice_number' => $invoiceNumber, 'response' => $response]);
    } catch (\Exception $e) {
        $this->error('Error during Doku test: '.$e->getMessage());
        Log::error('doku:test error', ['invoice_number' => $invoiceNumber, 'error' => $e->getMessage()]);
    }
})->purpose('Test Doku Checkout integration using DokuFunction trait');
