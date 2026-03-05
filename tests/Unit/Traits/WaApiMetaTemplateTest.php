<?php

namespace Tests\Unit\Traits;

use App\Traits\WaApiMetaTemplate;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WaApiMetaTemplateHarness
{
    use WaApiMetaTemplate;

    public function publicGetTemplates(array $fields = [], ?int $limit = null): ?array
    {
        return $this->getTemplates($fields ?: ['id', 'name'], $limit);
    }

    public function publicGetTemplateNamespace(): ?string
    {
        return $this->getTemplateNamespace();
    }
}

describe('WaApiMetaTemplate', function () {
    it('returns null when API is disabled', function () {
        Config::set('services.whatsapp.enabled', false);
        $h = new WaApiMetaTemplateHarness;

        expect($h->publicGetTemplates())->toBeNull();
        expect($h->publicGetTemplateNamespace())->toBeNull();
    });

    it('returns null when configuration is missing', function () {
        Config::set('services.whatsapp', ['enabled' => true]);
        $h = new WaApiMetaTemplateHarness;

        expect($h->publicGetTemplates())->toBeNull();
        expect($h->publicGetTemplateNamespace())->toBeNull();
    });

    it('retrieves templates successfully', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['data' => [['id' => '1', 'name' => 't1']]], 200),
        ]);

        $h = new WaApiMetaTemplateHarness;
        $result = $h->publicGetTemplates(['id', 'name'], 10);

        expect($result['data'][0]['name'])->toBe('t1');
    });

    it('retries with reduced fields on Graph API code 100', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::sequence()
                ->push(['error' => ['code' => 100]], 400)
                ->push(['data' => [['id' => '1', 'name' => 't1']]], 200),
            'https://graph.test/waba1*' => Http::response(['message_template_namespace' => 'ns1'], 200),
        ]);

        $h = new WaApiMetaTemplateHarness;
        $result = $h->publicGetTemplates(['id', 'name', 'bad_field'], 5);
        expect($result['data'][0]['id'])->toBe('1');

        expect($h->publicGetTemplateNamespace())->toBe('ns1');
    });

    it('returns api error payload when non-100 error occurs', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['error' => ['code' => 200, 'message' => 'bad']], 400),
        ]);

        $h = new WaApiMetaTemplateHarness;
        $res = $h->publicGetTemplates(['id', 'name'], 1);
        expect($res['error']['message'])->toBe('bad');
    });

    it('returns retry payload when retry fails', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::sequence()
                ->push(['error' => ['code' => 100]], 400)
                ->push(['error' => ['code' => 400, 'message' => 'still bad']], 400),
        ]);

        $h = new WaApiMetaTemplateHarness;
        $res = $h->publicGetTemplates(['id', 'name', 'bad_field'], 5);
        expect($res['error']['message'])->toBe('still bad');
    });

    it('returns null when http client throws', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake(function () {
            throw new Exception('boom');
        });

        $h = new WaApiMetaTemplateHarness;
        expect($h->publicGetTemplates())->toBeNull();
        expect($h->publicGetTemplateNamespace())->toBeNull();
    });

    it('returns null when namespace endpoint is non-2xx', function () {
        Config::set('services.whatsapp', [
            'enabled' => true,
            'endpoint' => 'https://graph.test',
            'business_id' => 'waba1',
            'access_token' => 'token',
        ]);

        Http::fake([
            'https://graph.test/waba1*' => Http::response(['error' => 'bad'], 500),
        ]);

        $h = new WaApiMetaTemplateHarness;
        expect($h->publicGetTemplateNamespace())->toBeNull();
    });
});
