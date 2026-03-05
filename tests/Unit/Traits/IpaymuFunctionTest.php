<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class IpaymuFunctionHarness
{
    use App\Traits\IpaymuFunction;
}

describe('IpaymuFunction', function () {
    it('returns null when disabled', function () {
        Config::set('services.ipaymu', ['enabled' => false]);
        $h = new IpaymuFunctionHarness;

        expect($h->createIpaymuPayment(['referenceId' => 'REF-1']))->toBeNull();
    });

    it('throws when enabled but missing credentials', function () {
        Config::set('services.ipaymu', ['enabled' => true, 'production' => false]);
        $h = new IpaymuFunctionHarness;

        $h->createIpaymuPayment(['referenceId' => 'REF-1']);
    })->throws(RuntimeException::class);

    it('creates payment successfully and returns decoded response', function () {
        Config::set('services.ipaymu', [
            'enabled' => true,
            'va' => 'va123',
            'api_key' => 'apikey',
            'production' => false,
            'api_sandbox' => 'https://sandbox.ipaymu.test',
        ]);

        Http::fake([
            'https://sandbox.ipaymu.test/api/v2/payment' => Http::response([
                'Status' => 200,
                'Success' => true,
                'Data' => ['SessionID' => 'sid', 'Url' => 'https://pay.test/#/sid'],
            ], 200),
        ]);

        $h = new IpaymuFunctionHarness;
        $result = $h->createIpaymuPayment(['referenceId' => 'REF-1', 'product' => ['A'], 'qty' => [1], 'price' => [1000]]);

        expect($result['Data']['SessionID'])->toBe('sid');
    });

    it('returns error payload when API fails', function () {
        Config::set('services.ipaymu', [
            'enabled' => true,
            'va' => 'va123',
            'api_key' => 'apikey',
            'production' => false,
            'api_sandbox' => 'https://sandbox.ipaymu.test',
        ]);

        Http::fake([
            'https://sandbox.ipaymu.test/api/v2/payment' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new IpaymuFunctionHarness;
        $result = $h->createIpaymuPayment(['referenceId' => 'REF-1']);

        expect($result['status'])->toBe('error');
        expect($result['http_status'])->toBe(500);
    });

    it('handles notifications and redirect normalization', function () {
        $h = new IpaymuFunctionHarness;

        $payload = [
            'trx_id' => 123,
            'reference_id' => 'REF-1',
            'status' => 'berhasil',
            'status_code' => 1,
            'amount' => '10000',
        ];

        $req = Request::create('/ipaymu', 'POST', $payload);
        $result = $h->handleIpaymuNotification($req);
        expect($result['normalized_status'])->toBe('paid');

        $redirect = $h->captureIpaymuRedirectData(['sid' => 'sid', 'trx_id' => 123, 'reference_id' => 'REF-1', 'status' => 'pending']);
        expect($redirect['status_short'])->toBe('pending');
    });
});
