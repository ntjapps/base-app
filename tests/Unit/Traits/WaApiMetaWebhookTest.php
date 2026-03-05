<?php

namespace Tests\Unit\Traits;

use App\Interfaces\GoQueues;
use App\Traits\WaApiMetaWebhook;
use ReflectionClass;
use RuntimeException;

class WaApiMetaWebhookHarness
{
    use WaApiMetaWebhook;

    public bool $shouldThrow = false;

    public function sendGoTask(...$args): string
    {
        if ($this->shouldThrow) {
            throw new RuntimeException('sendGoTask failed');
        }

        return 'task-id';
    }
}

describe('WaApiMetaWebhook', function () {
    it('dispatches inbound payload via go worker', function () {
        $h = new WaApiMetaWebhookHarness;
        expect($h->processWebhookMessages(['entry' => []]))->toBeTrue();

        $h->shouldThrow = true;
        expect($h->processWebhookMessages(['entry' => []]))->toBeFalse();
    });

    it('executes legacy processing helpers without error', function () {
        $h = new WaApiMetaWebhookHarness;
        $ref = new ReflectionClass($h);

        $fixtures = [
            'processBusinessCapabilityUpdateField' => [
                'max_daily_conversation_per_phone' => 10,
                'max_phone_numbers_per_business' => 5,
            ],
            'processBusinessStatusUpdateField' => [
                'business_id' => 'b1',
                'event' => 'updated',
            ],
            'processCallsField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'calls' => [['id' => 'c1', 'to' => '1', 'from' => '2', 'timestamp' => 't', 'event' => 'ringing']],
                'contacts' => [['wa_id' => 'wa1', 'profile' => ['name' => 'John']]],
            ],
            'processFlowsField' => [
                'event' => 'flow',
                'message' => 'm',
                'flow_id' => 'f1',
            ],
            'processHistoryField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'history' => [[
                    'metadata' => ['phase' => 'phase1', 'chunk_order' => 1, 'progress' => 50],
                    'threads' => [[
                        'id' => 'th1',
                        'messages' => [[
                            'from' => 'wa1',
                            'id' => 'm1',
                            'timestamp' => 't',
                            'type' => 'text',
                            'text' => ['body' => 'hello'],
                            'history_context' => ['status' => 'delivered', 'from_me' => false],
                        ]],
                    ]],
                ]],
            ],
            'processMessageEchoesField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'message_echoes' => [[
                    'from' => 'wa1',
                    'to' => 'me',
                    'id' => 'e1',
                    'timestamp' => 't',
                    'type' => 'text',
                    'message_creation_type' => 'user',
                    'text' => ['body' => 'echo'],
                ]],
            ],
            'processMessagingHandoversField' => [
                'messaging_product' => 'whatsapp',
                'recipient' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'sender' => ['phone_number' => '2'],
                'timestamp' => 't',
                'control_passed' => ['metadata' => ['key' => 'value']],
            ],
            'processPartnerSolutionsField' => [
                'event' => 'event',
                'solution_id' => 's1',
                'solution_status' => 'active',
            ],
            'processPaymentConfigurationUpdateField' => [
                'configuration_name' => 'cfg',
                'provider_name' => 'prov',
                'provider_mid' => 'mid',
                'status' => 'active',
                'created_timestamp' => 'c',
                'updated_timestamp' => 'u',
            ],
            'processPhoneNumberNameUpdateField' => [
                'display_phone_number' => '1',
                'decision' => 'approved',
                'requested_verified_name' => 'Name',
                'rejection_reason' => null,
            ],
            'processPhoneNumberQualityUpdateField' => [
                'display_phone_number' => '1',
                'event' => 'quality',
                'current_limit' => 100,
            ],
            'processSecurityField' => [
                'display_phone_number' => '1',
                'event' => 'security',
                'requester' => 'me',
            ],
            'processSmbAppStateSyncField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'state_sync' => [[
                    'type' => 'contact',
                    'action' => 'create',
                    'metadata' => ['timestamp' => 't', 'version' => 'v1'],
                    'contact' => ['full_name' => 'John Doe', 'first_name' => 'John', 'phone_number' => '2'],
                ]],
            ],
            'processSmbMessageEchoesField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'message_echoes' => [[
                    'from' => 'wa1',
                    'to' => 'me',
                    'id' => 'e1',
                    'timestamp' => 't',
                    'type' => 'text',
                    'text' => ['body' => 'echo'],
                ]],
            ],
            'processTemplateCategoryUpdateField' => [
                'message_template_id' => 't1',
                'message_template_name' => 'name',
                'message_template_language' => 'en',
                'previous_category' => 'MARKETING',
                'new_category' => 'UTILITY',
                'correct_category' => 'UTILITY',
            ],
            'processTrackingEventsField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'events' => [[
                    'event_name' => 'click',
                    'timestamp' => 't',
                    'tracking_data' => ['click_id' => 'c1', 'tracking_token' => 'tok'],
                ]],
            ],
            'processUserPreferencesField' => [
                'messaging_product' => 'whatsapp',
                'metadata' => ['display_phone_number' => '1', 'phone_number_id' => 'p1'],
                'contacts' => [['wa_id' => 'wa1', 'profile' => ['name' => 'John']]],
                'user_preferences' => [[
                    'wa_id' => 'wa1',
                    'detail' => 'd',
                    'category' => 'c',
                    'value' => 'v',
                    'timestamp' => 't',
                ]],
            ],
        ];

        foreach ($fixtures as $method => $fixture) {
            $m = $ref->getMethod($method);
            $m->setAccessible(true);
            $m->invoke($h, $fixture);
        }

        expect(GoQueues::WHATSAPP)->toBeString();
    });
});
