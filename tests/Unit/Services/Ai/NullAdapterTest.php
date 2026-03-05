<?php

use App\Services\Ai\Adapters\NullAdapter;

describe('NullAdapter', function () {
    it('returns deterministic disabled responses', function () {
        $a = new NullAdapter;
        expect($a->isEnabled())->toBeFalse();
        expect($a->getName())->toBe('null');
        expect($a->getModel())->toBe('none');

        $res = $a->sendPrompt('hi');
        expect($res->success)->toBeTrue();
        expect($res->text)->toContain('placeholder');

        $res2 = $a->sendPromptWithTools('hi', []);
        expect($res2->success)->toBeTrue();
    });
});
