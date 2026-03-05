<?php

use App\Interfaces\RoleConstants;
use App\Jobs\WhatsApp\CreateTemplateJob;
use App\Jobs\WhatsApp\DeleteTemplateJob;
use App\Jobs\WhatsApp\UpdateTemplateJob;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

describe('WhatsappTemplateManController', function () {
    beforeEach(function () {
        $u = User::factory()->create();
        $u->syncRoles([RoleConstants::SUPER_ADMIN]);
        $this->actingAs($u, 'api');

        Gate::shouldReceive('forUser')->andReturnSelf();
        Gate::shouldReceive('authorize')->andReturnTrue();
    });

    it('returns empty list when API is disabled', function () {
        config()->set('services.whatsapp.enabled', false);
        $this->getJson(route('whatsapp-templates-list'))->assertStatus(200)->assertJson(['data' => []]);
    });

    it('returns templates when API responds with data', function () {
        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.endpoint', 'https://graph.test');
        config()->set('services.whatsapp.business_id', 'waba1');
        config()->set('services.whatsapp.access_token', 'token');

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['data' => [['id' => '1', 'name' => 't1']]], 200),
        ]);

        $this->getJson(route('whatsapp-templates-list'))->assertStatus(200)->assertJsonPath('data.0.name', 't1');
    });

    it('dispatches create and update jobs', function () {
        Bus::fake();
        $this->postJson(route('whatsapp-template-create'), [
            'name' => 't1',
            'language' => 'id',
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $this->patchJson(route('whatsapp-template-update', ['templateId' => 'tpl1']), [
            'category' => 'UTILITY',
            'components' => [['type' => 'BODY', 'text' => 'Hello']],
            'message_send_ttl_seconds' => 60,
            'cta_url_link_tracking_opted_out' => true,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        Bus::assertDispatched(CreateTemplateJob::class);
        Bus::assertDispatched(UpdateTemplateJob::class);
    });

    it('returns validation errors for create and edit', function () {
        $this->postJson(route('whatsapp-template-create'), [
            'name' => 't1',
            'language' => 'id',
            'category' => 'UTILITY',
        ])->assertStatus(422);

        $this->patchJson(route('whatsapp-template-update', ['templateId' => 'tpl1']), [
            'category' => 'BAD',
        ])->assertStatus(422);
    });

    it('dispatches delete job when template exists', function () {
        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.endpoint', 'https://graph.test');
        config()->set('services.whatsapp.business_id', 'waba1');
        config()->set('services.whatsapp.access_token', 'token');

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['data' => [['id' => 'tpl1', 'name' => 't1']]], 200),
        ]);

        Bus::fake();
        $this->deleteJson(route('whatsapp-template-delete', ['templateId' => 'tpl1']))
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        Bus::assertDispatched(DeleteTemplateJob::class);
    });

    it('returns validation errors for delete when template missing', function () {
        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.endpoint', 'https://graph.test');
        config()->set('services.whatsapp.business_id', 'waba1');
        config()->set('services.whatsapp.access_token', 'token');

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['data' => []], 200),
        ]);

        $this->deleteJson(route('whatsapp-template-delete', ['templateId' => 'tpl1']))->assertStatus(422);
    });

    it('returns validation error when WhatsApp API responds with error payload', function () {
        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.endpoint', 'https://graph.test');
        config()->set('services.whatsapp.business_id', 'waba1');
        config()->set('services.whatsapp.access_token', 'token');

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['error' => ['message' => 'bad']], 400),
        ]);

        $this->deleteJson(route('whatsapp-template-delete', ['templateId' => 'tpl1']))->assertStatus(422);
    });

    it('returns validation error when template name is unavailable', function () {
        config()->set('services.whatsapp.enabled', true);
        config()->set('services.whatsapp.endpoint', 'https://graph.test');
        config()->set('services.whatsapp.business_id', 'waba1');
        config()->set('services.whatsapp.access_token', 'token');

        Http::fake([
            'https://graph.test/waba1/message_templates*' => Http::response(['data' => [['id' => 'tpl1']]], 200),
        ]);

        $this->deleteJson(route('whatsapp-template-delete', ['templateId' => 'tpl1']))->assertStatus(422);
    });

    it('exposes template management page', function () {
        $this->get(route('whatsapp-templates-man'))->assertStatus(200);
    });
});
