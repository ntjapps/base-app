<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class WahaApiHarness
{
    use App\Traits\WahaApi;

    public function call(string $method, ...$args)
    {
        $ref = new ReflectionClass($this);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($this, ...$args);
    }
}

describe('WahaApi', function () {
    it('throws when base_url is missing', function () {
        Config::set('services.waha.base_url', null);
        $h = new WahaApiHarness;

        $h->call('wahaSendSeen', 'c1');
    })->throws(Exception::class);

    it('handles contact check and messaging calls', function () {
        Config::set('services.waha.base_url', 'https://waha.test');
        Config::set('services.waha.session', 's1');

        Http::fake([
            'https://waha.test/api/contacts/check-exists*' => Http::sequence()
                ->push(['exists' => true], 200)
                ->push(['error' => true], 500),
            'https://waha.test/api/sendSeen' => Http::response(['ok' => true], 200),
            'https://waha.test/api/startTyping' => Http::response(['ok' => true], 200),
            'https://waha.test/api/stopTyping' => Http::response(['ok' => true], 200),
            'https://waha.test/api/sendText' => Http::response(['ok' => true], 200),
        ]);

        $h = new WahaApiHarness;
        $res = $h->call('wahaCheckContactExists', '6281');
        expect($res['exists'])->toBeTrue();
        expect($h->call('wahaCheckContactExists', '6281'))->toBeNull();

        $h->call('wahaSendSeen', 'c1');
        $h->call('wahaStartTyping', 'c1');
        $h->call('wahaStopTyping', 'c1');
        $h->call('wahaSendText', 'c1', 'hello');
    });
});
