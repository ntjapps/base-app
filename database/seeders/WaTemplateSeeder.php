<?php

namespace Database\Seeders;

use App\Models\WaTemplate;
use App\Models\WaTemplateVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WaTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates sample WhatsApp templates for development/testing.
     * In production, templates should be synced from the provider via reconciliation job.
     */
    public function run(): void
    {
        $this->command->info('🔄 Seeding WhatsApp templates...');

        $templates = [
            [
                'provider_id' => 'meta_'.Str::random(10),
                'name' => 'welcome_message',
                'library_template_name' => null,
                'language' => 'en',
                'category' => 'MARKETING',
                'sub_category' => null,
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => 'Welcome to Our Service!',
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Hi {{1}}, thank you for joining us. We\'re excited to have you on board!',
                    ],
                    [
                        'type' => 'FOOTER',
                        'text' => 'Reply STOP to unsubscribe',
                    ],
                ],
                'status' => 'APPROVED',
                'quality_score' => 95,
                'change_reason' => 'Initial template creation',
            ],
            [
                'provider_id' => 'meta_'.Str::random(10),
                'name' => 'order_confirmation',
                'library_template_name' => null,
                'language' => 'en',
                'category' => 'UTILITY',
                'sub_category' => 'ORDER_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Your order #{{1}} has been confirmed. Estimated delivery: {{2}}.',
                    ],
                    [
                        'type' => 'BUTTONS',
                        'buttons' => [
                            [
                                'type' => 'URL',
                                'text' => 'Track Order',
                                'url' => 'https://example.com/track/{{1}}',
                            ],
                        ],
                    ],
                ],
                'status' => 'APPROVED',
                'quality_score' => 98,
                'message_send_ttl_seconds' => 3600,
                'change_reason' => 'Initial template creation',
            ],
            [
                'provider_id' => 'meta_'.Str::random(10),
                'name' => 'appointment_reminder',
                'library_template_name' => null,
                'language' => 'en',
                'category' => 'UTILITY',
                'sub_category' => 'APPOINTMENT_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Reminder: You have an appointment on {{1}} at {{2}}. Please arrive 10 minutes early.',
                    ],
                    [
                        'type' => 'BUTTONS',
                        'buttons' => [
                            [
                                'type' => 'QUICK_REPLY',
                                'text' => 'Confirm',
                            ],
                            [
                                'type' => 'QUICK_REPLY',
                                'text' => 'Reschedule',
                            ],
                        ],
                    ],
                ],
                'status' => 'APPROVED',
                'quality_score' => 92,
                'change_reason' => 'Initial template creation',
            ],
            [
                'provider_id' => 'meta_'.Str::random(10),
                'name' => 'promotional_offer',
                'library_template_name' => null,
                'language' => 'en',
                'category' => 'MARKETING',
                'sub_category' => null,
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'IMAGE',
                        'example' => ['header_handle' => ['https://example.com/promo.jpg']],
                    ],
                    [
                        'type' => 'BODY',
                        'text' => 'Special offer just for you! Get {{1}}% off your next purchase. Use code: {{2}}',
                    ],
                    [
                        'type' => 'BUTTONS',
                        'buttons' => [
                            [
                                'type' => 'URL',
                                'text' => 'Shop Now',
                                'url' => 'https://example.com/shop',
                            ],
                        ],
                    ],
                ],
                'status' => 'PENDING',
                'quality_score' => null,
                'change_reason' => 'Pending approval from Meta',
            ],
            [
                'provider_id' => 'meta_'.Str::random(10),
                'name' => 'payment_failed',
                'library_template_name' => null,
                'language' => 'en',
                'category' => 'UTILITY',
                'sub_category' => 'PAYMENT_UPDATE',
                'components' => [
                    [
                        'type' => 'BODY',
                        'text' => 'Your payment for order #{{1}} failed. Please update your payment method to complete the purchase.',
                    ],
                    [
                        'type' => 'BUTTONS',
                        'buttons' => [
                            [
                                'type' => 'URL',
                                'text' => 'Update Payment',
                                'url' => 'https://example.com/payment/{{1}}',
                            ],
                        ],
                    ],
                ],
                'status' => 'REJECTED',
                'quality_score' => null,
                'rejected_reason' => 'Template content does not comply with WhatsApp policy. Please revise.',
                'change_reason' => 'Rejected by Meta',
            ],
        ];

        foreach ($templates as $templateData) {
            $changeReason = $templateData['change_reason'];
            unset($templateData['change_reason']);

            $templateData['last_synced_at'] = now();

            $template = WaTemplate::create($templateData);

            // Create initial version
            WaTemplateVersion::create([
                'wa_template_id' => $template->id,
                'version' => 1,
                'snapshot' => $templateData,
                'changed_by_user_id' => null,
                'change_reason' => $changeReason,
                'provider_event' => null,
            ]);

            $this->command->info("✅ Created template: {$template->name} ({$template->status})");
        }

        $this->command->info('✅ Seeded '.count($templates).' WhatsApp templates with versions');
    }
}
