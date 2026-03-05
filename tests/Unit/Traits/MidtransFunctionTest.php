<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class MidtransFunctionHarness
{
    use App\Traits\MidtransFunction;
}

describe('MidtransFunction', function () {
    it('returns null when disabled', function () {
        Config::set('services.midtrans', ['enabled' => false]);
        $h = new MidtransFunctionHarness;

        expect($h->createSnapTransaction('ORDER-1', 10000))->toBeNull();
    });

    it('throws when enabled but missing server key', function () {
        Config::set('services.midtrans', ['enabled' => true, 'production' => false]);
        $h = new MidtransFunctionHarness;

        $h->createSnapTransaction('ORDER-1', 10000);
    })->throws(RuntimeException::class);

    it('creates snap transaction successfully', function () {
        Config::set('services.midtrans', [
            'enabled' => true,
            'server_key' => 'serverkey',
            'production' => false,
            'api_sandbox' => 'https://snap.sandbox.midtrans.test/transactions',
            'finish_url' => 'https://example.test/finish',
        ]);

        Http::fake([
            'https://snap.sandbox.midtrans.test/transactions' => Http::response([
                'token' => 'tok',
                'redirect_url' => 'https://redirect.test',
            ], 200),
        ]);

        $h = new MidtransFunctionHarness;
        $result = $h->createSnapTransaction('ORDER-1', 10000, ['first_name' => 'A'], [['id' => 'i1']], ['custom' => 1]);

        expect($result['token'])->toBe('tok');
        expect($result['redirect_url'])->toBe('https://redirect.test');
    });

    it('returns error payload when API fails', function () {
        Config::set('services.midtrans', [
            'enabled' => true,
            'server_key' => 'serverkey',
            'production' => false,
            'api_sandbox' => 'https://snap.sandbox.midtrans.test/transactions',
        ]);

        Http::fake([
            'https://snap.sandbox.midtrans.test/transactions' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new MidtransFunctionHarness;
        $result = $h->createSnapTransaction('ORDER-1', 10000);

        expect($result['status'])->toBe('error');
        expect($result['http_status'])->toBe(500);
    });

    it('verifies notification signatures', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        $body = [
            'order_id' => 'ORDER-1',
            'status_code' => '200',
            'gross_amount' => '10000.00',
        ];
        $body['signature_key'] = hash('sha512', $body['order_id'].$body['status_code'].$body['gross_amount'].'serverkey');

        expect($h->verifyNotificationSignature($body))->toBeTrue();
        expect($h->verifyNotificationSignature(array_merge($body, ['signature_key' => 'bad'])))->toBeFalse();
    });

    it('returns false when signature verification inputs are incomplete', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        expect($h->verifyNotificationSignature([]))->toBeFalse();
        expect($h->verifyNotificationSignature(['order_id' => 'o']))->toBeFalse();
    });

    it('returns false when server key is missing', function () {
        Config::set('services.midtrans', []);
        $h = new MidtransFunctionHarness;

        $body = [
            'order_id' => 'ORDER-1',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'signature_key' => 'x',
        ];

        expect($h->verifyNotificationSignature($body))->toBeFalse();
    });

    it('handles notification and redirect normalization', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        $body = [
            'order_id' => 'ORDER-1',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'transaction_status' => 'capture',
            'payment_type' => 'credit_card',
            'fraud_status' => 'challenge',
        ];
        $body['signature_key'] = hash('sha512', $body['order_id'].$body['status_code'].$body['gross_amount'].'serverkey');

        $req = Request::create('/midtrans', 'POST', $body);
        $result = $h->handleNotification($req);
        expect($result['status'])->toBe('challenge');
        expect($result['signature_valid'])->toBeTrue();

        $redirect = $h->captureRedirectData(['order_id' => 'ORDER-1', 'status_code' => '200', 'transaction_status' => 'pending']);
        expect($redirect['status'])->toBe('pending');
    });

    it('normalizes many transaction status variants', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        $base = [
            'order_id' => 'ORDER-1',
            'status_code' => '200',
            'gross_amount' => '10000.00',
        ];
        $base['signature_key'] = hash('sha512', $base['order_id'].$base['status_code'].$base['gross_amount'].'serverkey');

        $paid = $h->handleNotification(array_merge($base, [
            'transaction_status' => 'capture',
            'payment_type' => 'credit_card',
            'fraud_status' => 'accept',
        ]));
        expect($paid['status'])->toBe('paid');

        $paid2 = $h->handleNotification(array_merge($base, [
            'transaction_status' => 'capture',
            'payment_type' => 'gopay',
        ]));
        expect($paid2['status'])->toBe('paid');

        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'settlement']))['status'])->toBe('paid');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'pending']))['status'])->toBe('pending');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'deny']))['status'])->toBe('failed');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'cancel']))['status'])->toBe('failed');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'expire']))['status'])->toBe('expired');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'refund']))['status'])->toBe('refunded');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'partial_refund']))['status'])->toBe('partial_refunded');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'chargeback']))['status'])->toBe('chargeback');
        expect($h->handleNotification(array_merge($base, ['transaction_status' => 'unknown']))['status'])->toBe('unknown');
    });

    it('normalizes invalid signature notifications', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        $body = [
            'order_id' => 'ORDER-1',
            'status_code' => '200',
            'gross_amount' => '10000.00',
            'transaction_status' => 'pending',
            'signature_key' => 'bad',
        ];

        $res = $h->handleNotification($body);
        expect($res['signature_valid'])->toBeFalse();
        expect($res['status'])->toBe('pending');
    });

    it('captures redirect data from requests and objects', function () {
        Config::set('services.midtrans', ['server_key' => 'serverkey']);
        $h = new MidtransFunctionHarness;

        $req = Request::create('/r', 'GET', ['order_id' => 'ORDER-1', 'status_code' => '200', 'transaction_status' => 'capture']);
        $fromReq = $h->captureRedirectData($req);
        expect($fromReq['status'])->toBe('paid');

        $obj = new class
        {
            public function toArray(): array
            {
                return ['order_id' => 'ORDER-1', 'status_code' => 200, 'transaction_status' => 'cancel'];
            }
        };
        $fromObj = $h->captureRedirectData($obj);
        expect($fromObj['status'])->toBe('failed');
    });
});
