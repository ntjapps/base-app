<?php

use App\Http\Controllers\WaApiController;

class WaApiControllerTestDouble extends WaApiController
{
    public bool $ok = true;

    public function processWebhookMessages(array $requestData): bool
    {
        return $this->ok;
    }
}

describe('WaApiController webhooks', function () {
    it('verifies webhook GET and handles disabled state', function () {
        config()->set('services.whatsapp.enabled', false);
        $this->get(route('whatsapp-webhook-get', ['veriId' => 'x']))->assertJson(['status' => 'failed']);

        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.verify_token', 'vt');

        $this->get(route('whatsapp-webhook-get', ['veriId' => 'x']).'?hub_mode=subscribe&hub_verify_token=vt&hub_challenge=abc')
            ->assertStatus(200)
            ->assertSeeText('abc');

        $this->get(route('whatsapp-webhook-get', ['veriId' => 'x']).'?hub_mode=subscribe&hub_verify_token=bad&hub_challenge=abc')
            ->assertStatus(403);
    });

    it('processes webhook POST with verification id', function () {
        config()->set('services.whatsapp.enabled', false);
        $this->postJson(route('whatsapp-webhook-post', ['veriId' => 'x']), ['entry' => []])
            ->assertStatus(422)
            ->assertJson(['status' => 'failed']);

        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.veriId', 'good');

        $this->postJson(route('whatsapp-webhook-post', ['veriId' => 'bad']), ['entry' => []])->assertStatus(200);

        $this->postJson(route('whatsapp-webhook-post', ['veriId' => 'good']), ['entry' => [['changes' => []]]])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);
    });
});
