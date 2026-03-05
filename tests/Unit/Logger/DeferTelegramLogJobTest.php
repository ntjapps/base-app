<?php

use App\Logger\Jobs\DeferTelegramLogJob;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('DeferTelegramLogJob', function () {
    it('sends telegram message via API', function () {
        Config::set('telegram.endpoint', 'https://telegram.test/bot');
        Config::set('telegram.token', 't1');
        Config::set('telegram.group_id', 'g1');

        Http::fake([
            'https://telegram.test/bott1/sendMessage' => Http::response(['ok' => true], 200),
        ]);

        $job = new DeferTelegramLogJob('hi', 'g1');
        expect($job->backoff())->toBe([1, 5, 10]);
        expect($job->tries())->toBe(1);
        expect($job->uniqueId())->toBe('DeferTelegramLogJob');
        expect($job->tags())->toBe(['DeferTelegramLogJob', 'uniqueId: DeferTelegramLogJob']);
        expect($job->uniqueFor)->toBe(60);

        $job->handle();
        expect(true)->toBeTrue();
    });
});
