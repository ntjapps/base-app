<?php

namespace Tests\Feature;

use App\Traits\WaApi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WaApiTraitTest extends TestCase
{
    use WaApi;

    /**
     * Test send message method.
     */
    public function test_send_message_method(): void
    {
        Http::shouldReceive('asForm->post')->once()->andReturn([
            'status' => true,
        ]);

        $this->sendMessage('81234567890', 'test');
    }

    /**
     * Test send fallback message method.
     */
    public function test_send_fallback_message_method(): void
    {
        Http::shouldReceive('asForm->post')->once()->andReturn([
            'status' => true,
        ]);

        $this->sendMessageFallback('81234567890', 'test');
    }

    /**
     * Test send schedule message method.
     */
    public function test_send_schedule_message_method(): void
    {
        Http::shouldReceive('asForm->post')->once()->andReturn([
            'status' => true,
        ]);

        $this->sendScheduledMessage('81234567890', 'test');
    }
}
