<?php

namespace Tests\Feature;

use App\Traits\TelegramApi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramApiTest extends TestCase
{
    use TelegramApi;

    /**
     * Test telegram available method.
     */
    public function test_telegram_available_method(): void
    {
        Http::shouldReceive('asForm->post')->once()->andReturn([
            'ok' => true,
        ]);

        $this->assertTrue($this->isTelegramApiAvailable());
    }

    /**
     * Test telegram send message method.
     */
    public function test_telegram_send_message_method(): void
    {
        Http::shouldReceive('asForm->post')->once()->andReturn([
            'ok' => true,
        ]);

        $this->assertTrue($this->sendTelegramMessage('test'));
    }
}
