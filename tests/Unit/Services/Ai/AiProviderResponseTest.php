<?php

use App\Services\Ai\Contracts\AiProviderResponse;

describe('AiProviderResponse', function () {
    it('creates success and failure responses', function () {
        $ok = AiProviderResponse::success('hi', [['name' => 'tool']], ['k' => 'v']);
        expect($ok->success)->toBeTrue();
        expect($ok->text)->toBe('hi');
        expect($ok->hasToolCalls())->toBeTrue();
        expect($ok->getMeta('k'))->toBe('v');

        $fail = AiProviderResponse::failure('boom', ['provider' => 'x']);
        expect($fail->success)->toBeFalse();
        expect($fail->text)->toBe('');
        expect($fail->error)->toBe('boom');
        expect($fail->hasToolCalls())->toBeFalse();
        expect($fail->getMeta('provider'))->toBe('x');
    });
});
