<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WaApiMetaAppManagementHarness
{
    use App\Traits\WaApiMetaAppManagement;

    public function publicRegister(?string $wabaId = null, ?string $accessToken = null, ?string $overrideCallbackUri = null, ?string $verifyToken = null): array
    {
        return $this->registerMetaAppWebhook($wabaId, $accessToken, $overrideCallbackUri, $verifyToken);
    }

    public function publicList(?string $wabaId = null, ?string $accessToken = null): array
    {
        return $this->listMetaAppSubscriptions($wabaId, $accessToken);
    }

    public function publicDelete(?string $wabaId = null, ?string $accessToken = null): array
    {
        return $this->deleteMetaAppSubscription($wabaId, $accessToken);
    }
}

describe('WaApiMetaAppManagement', function () {
    it('fails fast when configuration is missing', function () {
        Config::set('services.whatsapp', []);
        $h = new WaApiMetaAppManagementHarness;

        expect($h->publicRegister()['success'])->toBeFalse();
        expect($h->publicList()['success'])->toBeFalse();
        expect($h->publicDelete()['success'])->toBeFalse();
    });

    it('fails when waba id is missing', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => null,
            'access_token' => 'token',
        ]);

        $h = new WaApiMetaAppManagementHarness;
        expect($h->publicRegister()['error'])->toBe('missing waba id');
        expect($h->publicList()['error'])->toBe('missing waba id');
        expect($h->publicDelete()['error'])->toBe('missing waba id');
    });

    it('fails when access token is missing', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => null,
        ]);

        $h = new WaApiMetaAppManagementHarness;
        expect($h->publicRegister()['error'])->toBe('missing access token');
        expect($h->publicList()['error'])->toBe('missing access token');
        expect($h->publicDelete()['error'])->toBe('missing access token');
    });

    it('registers, lists, and deletes subscriptions successfully', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/subscribed_apps' => Http::sequence()
                ->push(['success' => true], 200)
                ->push(['data' => []], 200)
                ->push(['success' => true], 200),
        ]);

        $h = new WaApiMetaAppManagementHarness;
        expect($h->publicRegister(null, null, 'https://cb.test', 'vt')['success'])->toBeTrue();
        expect($h->publicList()['success'])->toBeTrue();
        expect($h->publicDelete()['success'])->toBeTrue();
    });

    it('returns http_error for non-2xx responses', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/subscribed_apps' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new WaApiMetaAppManagementHarness;
        $res = $h->publicRegister();
        expect($res['success'])->toBeFalse();
        expect($res['error'])->toBe('http_error');
    });

    it('returns http_error on list and delete when non-2xx', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/subscribed_apps' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new WaApiMetaAppManagementHarness;
        expect($h->publicList()['error'])->toBe('http_error');
        expect($h->publicDelete()['error'])->toBe('http_error');
    });

    it('returns exception message on http client exception', function () {
        Config::set('services.whatsapp', [
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake(function () {
            throw new Exception('boom');
        });

        $h = new WaApiMetaAppManagementHarness;
        expect($h->publicRegister()['error'])->toBe('boom');
        expect($h->publicList()['error'])->toBe('boom');
        expect($h->publicDelete()['error'])->toBe('boom');
    });
});
