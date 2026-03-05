<?php

namespace Tests\Unit\Traits;

use App\Traits\TelegramApi;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use ReflectionClass;

class TelegramApiHarness
{
    use TelegramApi;

    public function call(string $method, ...$args)
    {
        $ref = new ReflectionClass($this);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($this, ...$args);
    }
}

describe('TelegramApi', function () {
    it('checks availability and sends messages', function () {
        Config::set('telegram.endpoint', 'https://telegram.test/bot');
        Config::set('telegram.token', 't1');
        Config::set('telegram.group_id', 'g1');

        Http::fake([
            'https://telegram.test/bott1/getMe' => Http::response(['ok' => true], 200),
            'https://telegram.test/bott1/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $h = new TelegramApiHarness;
        expect($h->call('isTelegramApiAvailable'))->toBeTrue();
        expect($h->call('sendTelegramMessage', str_repeat('a', 5000), null))->toBeTrue();
    });

    it('returns false on connection exceptions', function () {
        Config::set('telegram.endpoint', 'https://telegram.test/bot');
        Config::set('telegram.token', 't1');
        Config::set('telegram.group_id', 'g1');

        Http::fake(function () {
            throw new ConnectionException('down');
        });

        $h = new TelegramApiHarness;
        expect($h->call('isTelegramApiAvailable'))->toBeFalse();
        expect($h->call('sendTelegramMessage', 'hi', null))->toBeFalse();
    });
});
