<?php

namespace Tests\Feature;

use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BasicMailTest extends TestCase
{
    /**
     * Test send mail
     */
    public function test_send_mail(): void
    {
        $mailable = new TestMail;

        $mailable->assertDontSeeInHtml('Hello');

        Mail::fake();

        Mail::assertNothingSent();

        Mail::to('zYt4i@example.com')->send(new TestMail);

        Mail::assertSent(TestMail::class);

        Mail::assertSent(TestMail::class, 'zYt4i@example.com');
    }
}
