<?php

namespace Tests\Feature;

use App\Exceptions\CommonCustomException;
use Illuminate\Http\Request;
use Tests\TestCase;

class BaseBasicAppTest extends TestCase
{
    /** Test CSRF Cookies Routes and Check if Cookies is set */
    public function test_csrf_cookie_route(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');
        $response->assertCookie('XSRF-TOKEN');
    }

    /** Test App Health Check and assert JSON Success */
    public function test_app_healthcheck_route(): void
    {
        $response = $this->get('/app/healthcheck');
        $response->assertOk();
    }

    /** Test login redirect with named route login is redirecting to landing page */
    public function test_login_redirect_route(): void
    {
        $response = $this->get('/login-redirect');
        $response->assertRedirect(route('landing-page'));
    }

    /** Test CommonCustomException */
    public function test_common_custom_exception(): void
    {
        $exception = new CommonCustomException;

        $this->assertInstanceOf(CommonCustomException::class, $exception);
        $this->assertEquals(422, $exception->getCode());
        $this->assertJson($exception->render(new Request)->getContent());
    }
}
