<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;

test('token revoke handles user token objects safely', function () {
    $tokenObj = new class
    {
        public bool $revoked = false;

        public function revoke(): void
        {
            $this->revoked = true;
        }
    };

    $userObj = new class($tokenObj)
    {
        public string $username = 'u1';

        public function __construct(private object $tokenObj) {}

        public function token(): object
        {
            return $this->tokenObj;
        }
    };

    $request = Request::create('/api/v1/auth/token', 'DELETE');
    $request->setUserResolver(fn () => $userObj);

    $controller = new AuthController;
    $res = $controller->postTokenRevoke($request);

    expect($res->getStatusCode())->toBe(200);
    expect($tokenObj->revoked)->toBeTrue();
});
