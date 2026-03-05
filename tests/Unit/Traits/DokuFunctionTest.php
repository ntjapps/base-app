<?php

namespace Tests\Unit\Traits;

use App\Traits\DokuFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use RuntimeException;

class DokuFunctionHarness
{
    use DokuFunction;
}

describe('DokuFunction', function () {
    it('returns null when disabled', function () {
        Config::set('services.doku', ['enabled' => false]);
        $h = new DokuFunctionHarness;

        expect($h->createDokuPayment(['order' => ['invoice_number' => 'INV-1']], 'req1'))->toBeNull();
    });

    it('throws when enabled but missing credentials', function () {
        Config::set('services.doku', ['enabled' => true, 'production' => false]);
        $h = new DokuFunctionHarness;

        $h->createDokuPayment(['order' => ['invoice_number' => 'INV-1']], 'req1');
    })->throws(RuntimeException::class);

    it('creates payment successfully and returns decoded response', function () {
        Config::set('services.doku', [
            'enabled' => true,
            'client_id' => 'client',
            'secret_key' => 'secret',
            'production' => false,
            'api_sandbox' => 'https://api-sandbox.doku.com',
        ]);

        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response([
                'response' => [
                    'order' => ['invoice_number' => 'INV-1'],
                    'payment' => ['url' => 'https://pay.test'],
                ],
            ], 200),
        ]);

        $h = new DokuFunctionHarness;
        $result = $h->createDokuPayment(['order' => ['invoice_number' => 'INV-1']], 'req1');

        expect($result['response']['order']['invoice_number'])->toBe('INV-1');
        expect($result['response']['payment']['url'])->toBe('https://pay.test');
    });

    it('returns error payload when API fails', function () {
        Config::set('services.doku', [
            'enabled' => true,
            'client_id' => 'client',
            'secret_key' => 'secret',
            'production' => false,
            'api_sandbox' => 'https://api-sandbox.doku.com',
        ]);

        Http::fake([
            'https://api-sandbox.doku.com/checkout/v1/payment' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new DokuFunctionHarness;
        $result = $h->createDokuPayment(['order' => ['invoice_number' => 'INV-1']], 'req1');

        expect($result['status'])->toBe('error');
        expect($result['http_status'])->toBe(500);
    });

    it('verifies notification signature and normalizes status', function () {
        Config::set('services.doku', [
            'enabled' => true,
            'client_id' => 'client',
            'secret_key' => 'secret',
        ]);

        $h = new DokuFunctionHarness;

        $payload = [
            'order' => ['invoice_number' => 'INV-1', 'amount' => 80000],
            'transaction' => ['status' => 'SUCCESS', 'date' => '2026-01-01'],
        ];
        $jsonBody = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $ref = new ReflectionClass($h);
        $m = $ref->getMethod('generateDokuSignature');
        $m->setAccessible(true);

        $clientId = 'client';
        $requestId = 'r1';
        $requestTimestamp = '2026-01-01T00:00:00Z';
        $secretKey = 'secret';
        $sigValue = $m->invoke($h, $jsonBody, $clientId, $requestId, $requestTimestamp, $secretKey, '/payments/notifications');
        $provided = 'HMACSHA256='.$sigValue;

        $request = Request::create('/doku', 'POST', [], [], [], [], $jsonBody);
        $request->headers->set('Client-Id', $clientId);
        $request->headers->set('Request-Id', $requestId);
        $request->headers->set('Request-Timestamp', $requestTimestamp);
        $request->headers->set('Signature', $provided);

        $result = $h->handleDokuNotification($request);
        expect($result['invoice_number'])->toBe('INV-1');
        expect($result['normalized_status'])->toBe('paid');
        expect($result['signature_valid'])->toBeTrue();
    });

    it('captures create response and redirect params', function () {
        $h = new DokuFunctionHarness;

        $resp = [
            'message' => ['SUCCESS'],
            'response' => [
                'order' => ['amount' => '80000', 'invoice_number' => 'INV-1', 'session_id' => 's1'],
                'payment' => ['url' => 'https://pay.test', 'token_id' => 't1', 'expired_date' => '2026-01-01'],
                'headers' => ['request_id' => 'r1', 'signature' => 'sig', 'date' => 'd', 'client_id' => 'c'],
            ],
        ];

        $normalized = $h->captureDokuCreateResponse($resp);
        expect($normalized['invoice_number'])->toBe('INV-1');
        expect($normalized['payment_url'])->toBe('https://pay.test');

        $redirect = $h->captureDokuRedirectData(['invoice_number' => 'INV-1', 'status' => 'SUCCESS']);
        expect($redirect['status_short'])->toBe('paid');
    });
});
