<?php

use App\Mail\TestMail;

describe('TestMail', function () {
    it('builds envelope and content', function () {
        $m = new TestMail;
        expect($m->envelope()->subject)->toBe('Test Mail');
        expect($m->content()->view)->toBe('email.test');
        expect($m->attachments())->toBe([]);
    });
});
