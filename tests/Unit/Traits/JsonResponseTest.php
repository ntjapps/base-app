<?php

namespace Tests\Unit\Traits;

use App\Traits\JsonResponse;

class JsonResponseHarness
{
    use JsonResponse;
}

describe('JsonResponse', function () {
    it('returns success responses with and without redirect', function () {
        $h = new JsonResponseHarness;

        $res1 = $h->jsonSuccess('t', 'm', 'r1', ['a' => 1]);
        expect($res1->getStatusCode())->toBe(200);
        expect($res1->getData(true))->toMatchArray([
            'status' => 'success',
            'title' => 't',
            'message' => 'm',
            'redirect' => 'r1',
            'data' => ['a' => 1],
        ]);

        $res2 = $h->jsonSuccess('t2', 'm2', null, null);
        expect($res2->getStatusCode())->toBe(200);
        expect($res2->getData(true))->toMatchArray([
            'status' => 'success',
            'title' => 't2',
            'message' => 'm2',
            'data' => null,
        ]);
    });

    it('returns failed responses with and without redirect', function () {
        $h = new JsonResponseHarness;

        $res1 = $h->jsonFailed('t', 'm', 'r1', ['a' => 1]);
        expect($res1->getStatusCode())->toBe(422);
        expect($res1->getData(true))->toMatchArray([
            'status' => 'failed',
            'redirect' => 'r1',
            'errors' => ['message' => 'm'],
            'data' => ['a' => 1],
        ]);

        $res2 = $h->jsonFailed('t2', 'm2', null, null);
        expect($res2->getStatusCode())->toBe(422);
        expect($res2->getData(true))->toMatchArray([
            'status' => 'failed',
            'errors' => ['message' => 'm2'],
            'data' => null,
        ]);
    });
});
