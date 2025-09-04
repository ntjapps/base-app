<?php

namespace App\Traits;

use App\Jobs\InboundMessage\WaMessageInboundJob;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use Illuminate\Support\Facades\Log;

trait WaApiMetaWebhook
{
    public function processWebhookMessages(array $requestData): bool
    {
        try {
            $entry = $requestData['entry'][0] ?? null;
            if ($entry && isset($entry['changes'][0]['field'])) {
                $field = $entry['changes'][0]['field'];
                $value = $entry['changes'][0]['value'];

                switch ($field) {
                    case 'messages':
                        if (! isset($entry['changes'][0]['value']['statuses'])) {
                            return $this->processMessagesField($value, $requestData);
                        } else {
                            $this->processMessageStatusField($value);
                        }
                        break;
                    case 'account_alerts':
                        $this->processAccountAlertsField($value);
                        break;
                    case 'account_review_update':
                        $this->processAccountReviewUpdateField($value);
                        break;
                    case 'account_settings_update':
                        $this->processAccountSettingsUpdateField($value);
                        break;
                    case 'account_update':
                        $this->processAccountUpdateField($value);
                        break;
                    case 'automatic_events':
                        $this->processAutomaticEventsField($value);
                        break;
                    case 'business_capability_update':
                        $this->processBusinessCapabilityUpdateField($value);
                        break;
                    case 'business_status_update':
                        $this->processBusinessStatusUpdateField($value);
                        break;
                    case 'calls':
                        $this->processCallsField($value);
                        break;
                    case 'flows':
                        $this->processFlowsField($value);
                        break;
                    case 'history':
                        $this->processHistoryField($value);
                        break;
                    case 'message_echoes':
                        $this->processMessageEchoesField($value);
                        break;
                    case 'message_template_components_update':
                        $this->processMessageTemplateComponentsUpdateField($value);
                        break;
                    case 'message_template_quality_update':
                        $this->processMessageTemplateQualityUpdateField($value);
                        break;
                    case 'message_template_status_update':
                        $this->processMessageTemplateStatusUpdateField($value);
                        break;
                    case 'messaging_handovers':
                        $this->processMessagingHandoversField($value);
                        break;
                    case 'partner_solutions':
                        $this->processPartnerSolutionsField($value);
                        break;
                    case 'payment_configuration_update':
                        $this->processPaymentConfigurationUpdateField($value);
                        break;
                    case 'phone_number_name_update':
                        $this->processPhoneNumberNameUpdateField($value);
                        break;
                    case 'phone_number_quality_update':
                        $this->processPhoneNumberQualityUpdateField($value);
                        break;
                    case 'security':
                        $this->processSecurityField($value);
                        break;
                    case 'smb_app_state_sync':
                        $this->processSmbAppStateSyncField($value);
                        break;
                    case 'smb_message_echoes':
                        $this->processSmbMessageEchoesField($value);
                        break;
                    case 'template_category_update':
                        $this->processTemplateCategoryUpdateField($value);
                        break;
                    case 'tracking_events':
                        $this->processTrackingEventsField($value);
                        break;
                    case 'user_preferences':
                        $this->processUserPreferencesField($value);
                        break;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp webhook: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return false;
    }

    private function processMessagesField(array $value, array $requestData): bool
    {
        // Extract metadata
        $metadata = $value['metadata'] ?? [];
        $phoneNumberId = $metadata['phone_number_id'] ?? null;
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;

        // Extract contact info
        $contact = $value['contacts'][0] ?? [];
        $contactWaId = $contact['wa_id'] ?? null;
        $contactName = $contact['profile']['name'] ?? null;

        // Extract message data
        $message = $value['messages'][0] ?? null;
        if ($message) {
            $waMessageWebhookLog = WaMessageWebhookLog::create([
                'phone_number_id' => $phoneNumberId,
                'display_phone_number' => $displayPhoneNumber,
                'contact_wa_id' => $contactWaId,
                'contact_name' => $contactName,
                'message_id' => $message['id'] ?? null,
                'message_from' => $message['from'] ?? null,
                'message_type' => $message['type'] ?? null,
                'message_body' => $message['type'] === 'text' ? ($message['text']['body'] ?? null) : null,
                'timestamp' => $message['timestamp'] ?? null,
                'raw_data' => $requestData,
            ]);

            Log::info('WhatsApp message saved to database', [
                'message_id' => $message['id'] ?? null,
                'from' => $message['from'] ?? null,
            ]);

            dispatch(new WaMessageInboundJob($waMessageWebhookLog));
        } else {
            Log::warning('No message data found in webhook entry', ['entry' => $value]);
        }

        return true;
    }

    private function processAccountAlertsField(array $value): void
    {
        // Process account alerts
        $entityType = $value['entity_type'] ?? null;
        $entityId = $value['entity_id'] ?? null;
        $alertSeverity = $value['alert_severity'] ?? null;
        $alertStatus = $value['alert_status'] ?? null;
        $alertType = $value['alert_type'] ?? null;
        $alertDescription = $value['alert_description'] ?? null;

        Log::info('Account alert received', [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'alert_severity' => $alertSeverity,
            'alert_status' => $alertStatus,
            'alert_type' => $alertType,
            'alert_description' => $alertDescription,
        ]);
    }

    private function processAccountReviewUpdateField(array $value): void
    {
        // Process account review update
        $decision = $value['decision'] ?? null;

        Log::info('Account review update received', [
            'decision' => $decision,
        ]);
    }

    private function processAccountSettingsUpdateField(array $value): void
    {
        // Process account settings update
        $messagingProduct = $value['messaging_product'] ?? null;
        $timestamp = $value['timestamp'] ?? null;
        $type = $value['type'] ?? null;

        $phoneNumberSettings = $value['phone_number_settings'] ?? [];
        $phoneNumberId = $phoneNumberSettings['phone_number_id'] ?? null;

        $calling = $phoneNumberSettings['calling'] ?? [];
        $callingStatus = $calling['status'] ?? null;
        $callIconVisibility = $calling['call_icon_visibility'] ?? null;
        $callbackPermissionStatus = $calling['callback_permission_status'] ?? null;

        $callHours = $calling['call_hours'] ?? [];
        $callHoursStatus = $callHours['status'] ?? null;

        $sip = $calling['sip'] ?? [];
        $sipStatus = $sip['status'] ?? null;

        Log::info('Account settings update received', [
            'messaging_product' => $messagingProduct,
            'timestamp' => $timestamp,
            'type' => $type,
            'phone_number_id' => $phoneNumberId,
            'calling_status' => $callingStatus,
            'call_icon_visibility' => $callIconVisibility,
            'callback_permission_status' => $callbackPermissionStatus,
            'call_hours_status' => $callHoursStatus,
            'sip_status' => $sipStatus,
        ]);
    }

    private function processAccountUpdateField(array $value): void
    {
        // Process account update
        $phoneNumber = $value['phone_number'] ?? null;
        $event = $value['event'] ?? null;

        Log::info('Account update received', [
            'phone_number' => $phoneNumber,
            'event' => $event,
        ]);
    }

    private function processAutomaticEventsField(array $value): void
    {
        // Process automatic events
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $automaticEvents = $value['automatic_events'] ?? [];

        // Log each automatic event
        foreach ($automaticEvents as $event) {
            $eventId = $event['id'] ?? null;
            $eventName = $event['event_name'] ?? null;
            $timestamp = $event['timestamp'] ?? null;

            Log::info('Automatic event received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'event_id' => $eventId,
                'event_name' => $eventName,
                'timestamp' => $timestamp,
            ]);
        }
    }

    private function processMessageStatusField(array $value): void
    {
        // Process message status updates
        $statuses = $value['statuses'] ?? [];

        // Log each status update
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $statusValue = $status['status'] ?? null;
            $timestamp = $status['timestamp'] ?? null;
            $recipientId = $status['recipient_id'] ?? null;

            // Extract conversation details
            $conversation = $status['conversation'] ?? [];
            $conversationId = $conversation['id'] ?? null;
            $expirationTimestamp = $conversation['expiration_timestamp'] ?? null;
            $originType = $conversation['origin']['type'] ?? null;

            // Extract pricing details
            $pricing = $status['pricing'] ?? [];
            $billable = $pricing['billable'] ?? null;
            $pricingModel = $pricing['pricing_model'] ?? null;
            $category = $pricing['category'] ?? null;
            $pricingType = $pricing['type'] ?? null;

            // Extract error details if present
            $errors = $status['errors'] ?? [];
            $errorDetails = [];
            foreach ($errors as $error) {
                $errorDetails[] = [
                    'code' => $error['code'] ?? null,
                    'title' => $error['title'] ?? null,
                    'message' => $error['message'] ?? null,
                    'details' => $error['error_data']['details'] ?? null,
                    'href' => $error['href'] ?? null,
                ];
            }

            Log::info('Message status update received', [
                'message_id' => $messageId,
                'status' => $statusValue,
                'timestamp' => $timestamp,
                'recipient_id' => $recipientId,
                'conversation_id' => $conversationId,
                'expiration_timestamp' => $expirationTimestamp,
                'origin_type' => $originType,
                'billable' => $billable,
                'pricing_model' => $pricingModel,
                'category' => $category,
                'pricing_type' => $pricingType,
                'errors' => $errorDetails,
            ]);
        }
    }

    private function processBusinessCapabilityUpdateField(array $value): void
    {
        // Process business capability update
        $maxDailyConversationPerPhone = $value['max_daily_conversation_per_phone'] ?? null;
        $maxPhoneNumbersPerBusiness = $value['max_phone_numbers_per_business'] ?? null;

        Log::info('Business capability update received', [
            'max_daily_conversation_per_phone' => $maxDailyConversationPerPhone,
            'max_phone_numbers_per_business' => $maxPhoneNumbersPerBusiness,
        ]);
    }

    private function processBusinessStatusUpdateField(array $value): void
    {
        // Process business status update
        $businessId = $value['business_id'] ?? null;
        $event = $value['event'] ?? null;

        Log::info('Business status update received', [
            'business_id' => $businessId,
            'event' => $event,
        ]);
    }

    private function processCallsField(array $value): void
    {
        // Process calls
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $calls = $value['calls'] ?? [];
        $contacts = $value['contacts'] ?? [];

        // Extract contact info
        $contact = $contacts[0] ?? [];
        $contactWaId = $contact['wa_id'] ?? null;
        $contactName = $contact['profile']['name'] ?? null;

        // Log each call
        foreach ($calls as $call) {
            $callId = $call['id'] ?? null;
            $callTo = $call['to'] ?? null;
            $callFrom = $call['from'] ?? null;
            $timestamp = $call['timestamp'] ?? null;
            $event = $call['event'] ?? null;

            Log::info('Call event received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'contact_wa_id' => $contactWaId,
                'contact_name' => $contactName,
                'call_id' => $callId,
                'call_to' => $callTo,
                'call_from' => $callFrom,
                'timestamp' => $timestamp,
                'event' => $event,
            ]);
        }
    }

    private function processFlowsField(array $value): void
    {
        // Process flows
        $event = $value['event'] ?? null;
        $message = $value['message'] ?? null;
        $flowId = $value['flow_id'] ?? null;

        Log::info('Flow event received', [
            'event' => $event,
            'message' => $message,
            'flow_id' => $flowId,
        ]);
    }

    private function processHistoryField(array $value): void
    {
        // Process history
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $history = $value['history'] ?? [];

        // Process each history entry
        foreach ($history as $historyEntry) {
            $historyMetadata = $historyEntry['metadata'] ?? [];
            $phase = $historyMetadata['phase'] ?? null;
            $chunkOrder = $historyMetadata['chunk_order'] ?? null;
            $progress = $historyMetadata['progress'] ?? null;

            $threads = $historyEntry['threads'] ?? [];

            // Process each thread
            foreach ($threads as $thread) {
                $threadId = $thread['id'] ?? null;
                $messages = $thread['messages'] ?? [];

                // Process each message in the thread
                foreach ($messages as $message) {
                    $messageFrom = $message['from'] ?? null;
                    $messageId = $message['id'] ?? null;
                    $timestamp = $message['timestamp'] ?? null;
                    $messageType = $message['type'] ?? null;

                    // Extract message content based on type
                    $messageBody = null;
                    if ($messageType === 'text' && isset($message['text']['body'])) {
                        $messageBody = $message['text']['body'];
                    }

                    // Extract history context
                    $historyContext = $message['history_context'] ?? [];
                    $status = $historyContext['status'] ?? null;
                    $fromMe = $historyContext['from_me'] ?? null;

                    Log::info('History message received', [
                        'messaging_product' => $messagingProduct,
                        'display_phone_number' => $displayPhoneNumber,
                        'phone_number_id' => $phoneNumberId,
                        'phase' => $phase,
                        'chunk_order' => $chunkOrder,
                        'progress' => $progress,
                        'thread_id' => $threadId,
                        'message_from' => $messageFrom,
                        'message_id' => $messageId,
                        'timestamp' => $timestamp,
                        'message_type' => $messageType,
                        'message_body' => $messageBody,
                        'status' => $status,
                        'from_me' => $fromMe,
                    ]);
                }
            }
        }
    }

    private function processMessageEchoesField(array $value): void
    {
        // Process message echoes
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $messageEchoes = $value['message_echoes'] ?? [];

        // Log each message echo
        foreach ($messageEchoes as $echo) {
            $messageFrom = $echo['from'] ?? null;
            $messageTo = $echo['to'] ?? null;
            $messageId = $echo['id'] ?? null;
            $timestamp = $echo['timestamp'] ?? null;
            $messageType = $echo['type'] ?? null;
            $messageCreationType = $echo['message_creation_type'] ?? null;

            // Extract message content based on type
            $messageBody = null;
            if ($messageType === 'text' && isset($echo['text']['body'])) {
                $messageBody = $echo['text']['body'];
            }

            Log::info('Message echo received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'message_from' => $messageFrom,
                'message_to' => $messageTo,
                'message_id' => $messageId,
                'timestamp' => $timestamp,
                'message_type' => $messageType,
                'message_creation_type' => $messageCreationType,
                'message_body' => $messageBody,
            ]);
        }
    }

    private function processMessageTemplateComponentsUpdateField(array $value): void
    {
        // Process message template components update
        $messageTemplateId = $value['message_template_id'] ?? null;
        $messageTemplateName = $value['message_template_name'] ?? null;
        $messageTemplateLanguage = $value['message_template_language'] ?? null;
        $messageTemplateTitle = $value['message_template_title'] ?? null;
        $messageTemplateElement = $value['message_template_element'] ?? null;
        $messageTemplateFooter = $value['message_template_footer'] ?? null;
        $messageTemplateButtons = $value['message_template_buttons'] ?? [];

        // Process buttons
        $buttons = [];
        foreach ($messageTemplateButtons as $button) {
            $buttons[] = [
                'type' => $button['message_template_button_type'] ?? null,
                'text' => $button['message_template_button_text'] ?? null,
                'url' => $button['message_template_button_url'] ?? null,
                'phone_number' => $button['message_template_button_phone_number'] ?? null,
            ];
        }

        Log::info('Message template components update received', [
            'message_template_id' => $messageTemplateId,
            'message_template_name' => $messageTemplateName,
            'message_template_language' => $messageTemplateLanguage,
            'message_template_title' => $messageTemplateTitle,
            'message_template_element' => $messageTemplateElement,
            'message_template_footer' => $messageTemplateFooter,
            'message_template_buttons' => $buttons,
        ]);
    }

    private function processMessageTemplateQualityUpdateField(array $value): void
    {
        // Process message template quality update
        $previousQualityScore = $value['previous_quality_score'] ?? null;
        $newQualityScore = $value['new_quality_score'] ?? null;
        $messageTemplateId = $value['message_template_id'] ?? null;
        $messageTemplateName = $value['message_template_name'] ?? null;
        $messageTemplateLanguage = $value['message_template_language'] ?? null;

        Log::info('Message template quality update received', [
            'previous_quality_score' => $previousQualityScore,
            'new_quality_score' => $newQualityScore,
            'message_template_id' => $messageTemplateId,
            'message_template_name' => $messageTemplateName,
            'message_template_language' => $messageTemplateLanguage,
        ]);
    }

    private function processMessageTemplateStatusUpdateField(array $value): void
    {
        // Process message template status update
        $event = $value['event'] ?? null;
        $messageTemplateId = $value['message_template_id'] ?? null;
        $messageTemplateName = $value['message_template_name'] ?? null;
        $messageTemplateLanguage = $value['message_template_language'] ?? null;
        $reason = $value['reason'] ?? null;

        Log::info('Message template status update received', [
            'event' => $event,
            'message_template_id' => $messageTemplateId,
            'message_template_name' => $messageTemplateName,
            'message_template_language' => $messageTemplateLanguage,
            'reason' => $reason,
        ]);
    }

    private function processMessagingHandoversField(array $value): void
    {
        // Process messaging handovers
        $messagingProduct = $value['messaging_product'] ?? null;

        $recipient = $value['recipient'] ?? [];
        $recipientDisplayPhoneNumber = $recipient['display_phone_number'] ?? null;
        $recipientPhoneNumberId = $recipient['phone_number_id'] ?? null;

        $sender = $value['sender'] ?? [];
        $senderPhoneNumber = $sender['phone_number'] ?? null;

        $timestamp = $value['timestamp'] ?? null;

        $controlPassed = $value['control_passed'] ?? [];
        $controlPassedMetadata = $controlPassed['metadata'] ?? null;

        Log::info('Messaging handovers received', [
            'messaging_product' => $messagingProduct,
            'recipient_display_phone_number' => $recipientDisplayPhoneNumber,
            'recipient_phone_number_id' => $recipientPhoneNumberId,
            'sender_phone_number' => $senderPhoneNumber,
            'timestamp' => $timestamp,
            'control_passed_metadata' => $controlPassedMetadata,
        ]);
    }

    private function processPartnerSolutionsField(array $value): void
    {
        // Process partner solutions
        $event = $value['event'] ?? null;
        $solutionId = $value['solution_id'] ?? null;
        $solutionStatus = $value['solution_status'] ?? null;

        Log::info('Partner solutions received', [
            'event' => $event,
            'solution_id' => $solutionId,
            'solution_status' => $solutionStatus,
        ]);
    }

    private function processPaymentConfigurationUpdateField(array $value): void
    {
        // Process payment configuration update
        $configurationName = $value['configuration_name'] ?? null;
        $providerName = $value['provider_name'] ?? null;
        $providerMid = $value['provider_mid'] ?? null;
        $status = $value['status'] ?? null;
        $createdTimestamp = $value['created_timestamp'] ?? null;
        $updatedTimestamp = $value['updated_timestamp'] ?? null;

        Log::info('Payment configuration update received', [
            'configuration_name' => $configurationName,
            'provider_name' => $providerName,
            'provider_mid' => $providerMid,
            'status' => $status,
            'created_timestamp' => $createdTimestamp,
            'updated_timestamp' => $updatedTimestamp,
        ]);
    }

    private function processPhoneNumberNameUpdateField(array $value): void
    {
        // Process phone number name update
        $displayPhoneNumber = $value['display_phone_number'] ?? null;
        $decision = $value['decision'] ?? null;
        $requestedVerifiedName = $value['requested_verified_name'] ?? null;
        $rejectionReason = $value['rejection_reason'] ?? null;

        Log::info('Phone number name update received', [
            'display_phone_number' => $displayPhoneNumber,
            'decision' => $decision,
            'requested_verified_name' => $requestedVerifiedName,
            'rejection_reason' => $rejectionReason,
        ]);
    }

    private function processPhoneNumberQualityUpdateField(array $value): void
    {
        // Process phone number quality update
        $displayPhoneNumber = $value['display_phone_number'] ?? null;
        $event = $value['event'] ?? null;
        $currentLimit = $value['current_limit'] ?? null;

        Log::info('Phone number quality update received', [
            'display_phone_number' => $displayPhoneNumber,
            'event' => $event,
            'current_limit' => $currentLimit,
        ]);
    }

    private function processSecurityField(array $value): void
    {
        // Process security event
        $displayPhoneNumber = $value['display_phone_number'] ?? null;
        $event = $value['event'] ?? null;
        $requester = $value['requester'] ?? null;

        Log::info('Security event received', [
            'display_phone_number' => $displayPhoneNumber,
            'event' => $event,
            'requester' => $requester,
        ]);
    }

    private function processSmbAppStateSyncField(array $value): void
    {
        // Process SMB app state sync
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $stateSync = $value['state_sync'] ?? [];

        // Log each state sync entry
        foreach ($stateSync as $sync) {
            $type = $sync['type'] ?? null;
            $action = $sync['action'] ?? null;

            $syncMetadata = $sync['metadata'] ?? [];
            $timestamp = $syncMetadata['timestamp'] ?? null;
            $version = $syncMetadata['version'] ?? null;

            // Extract contact info if type is contact
            $contact = $sync['contact'] ?? [];
            $fullName = $contact['full_name'] ?? null;
            $firstName = $contact['first_name'] ?? null;
            $phoneNumber = $contact['phone_number'] ?? null;

            Log::info('SMB app state sync received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'type' => $type,
                'action' => $action,
                'timestamp' => $timestamp,
                'version' => $version,
                'contact_full_name' => $fullName,
                'contact_first_name' => $firstName,
                'contact_phone_number' => $phoneNumber,
            ]);
        }
    }

    private function processSmbMessageEchoesField(array $value): void
    {
        // Process SMB message echoes
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $messageEchoes = $value['message_echoes'] ?? [];

        // Log each message echo
        foreach ($messageEchoes as $echo) {
            $messageFrom = $echo['from'] ?? null;
            $messageTo = $echo['to'] ?? null;
            $messageId = $echo['id'] ?? null;
            $timestamp = $echo['timestamp'] ?? null;
            $messageType = $echo['type'] ?? null;

            // Extract message content based on type
            $messageBody = null;
            if ($messageType === 'text' && isset($echo['text']['body'])) {
                $messageBody = $echo['text']['body'];
            }

            Log::info('SMB message echo received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'message_from' => $messageFrom,
                'message_to' => $messageTo,
                'message_id' => $messageId,
                'timestamp' => $timestamp,
                'message_type' => $messageType,
                'message_body' => $messageBody,
            ]);
        }
    }

    private function processTemplateCategoryUpdateField(array $value): void
    {
        // Process template category update
        $messageTemplateId = $value['message_template_id'] ?? null;
        $messageTemplateName = $value['message_template_name'] ?? null;
        $messageTemplateLanguage = $value['message_template_language'] ?? null;
        $previousCategory = $value['previous_category'] ?? null;
        $newCategory = $value['new_category'] ?? null;
        $correctCategory = $value['correct_category'] ?? null;

        Log::info('Template category update received', [
            'message_template_id' => $messageTemplateId,
            'message_template_name' => $messageTemplateName,
            'message_template_language' => $messageTemplateLanguage,
            'previous_category' => $previousCategory,
            'new_category' => $newCategory,
            'correct_category' => $correctCategory,
        ]);
    }

    private function processTrackingEventsField(array $value): void
    {
        // Process tracking events
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $events = $value['events'] ?? [];

        // Log each tracking event
        foreach ($events as $event) {
            $eventName = $event['event_name'] ?? null;
            $timestamp = $event['timestamp'] ?? null;

            $trackingData = $event['tracking_data'] ?? [];
            $clickId = $trackingData['click_id'] ?? null;
            $trackingToken = $trackingData['tracking_token'] ?? null;

            Log::info('Tracking event received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'event_name' => $eventName,
                'timestamp' => $timestamp,
                'click_id' => $clickId,
                'tracking_token' => $trackingToken,
            ]);
        }
    }

    private function processUserPreferencesField(array $value): void
    {
        // Process user preferences
        $messagingProduct = $value['messaging_product'] ?? null;

        $metadata = $value['metadata'] ?? [];
        $displayPhoneNumber = $metadata['display_phone_number'] ?? null;
        $phoneNumberId = $metadata['phone_number_id'] ?? null;

        $userPreferences = $value['user_preferences'] ?? [];
        $contacts = $value['contacts'] ?? [];

        // Extract contact info
        $contact = $contacts[0] ?? [];
        $contactWaId = $contact['wa_id'] ?? null;
        $contactName = $contact['profile']['name'] ?? null;

        // Log each user preference
        foreach ($userPreferences as $preference) {
            $waId = $preference['wa_id'] ?? null;
            $detail = $preference['detail'] ?? null;
            $category = $preference['category'] ?? null;
            $preferenceValue = $preference['value'] ?? null;
            $timestamp = $preference['timestamp'] ?? null;

            Log::info('User preference received', [
                'messaging_product' => $messagingProduct,
                'display_phone_number' => $displayPhoneNumber,
                'phone_number_id' => $phoneNumberId,
                'contact_wa_id' => $contactWaId,
                'contact_name' => $contactName,
                'wa_id' => $waId,
                'detail' => $detail,
                'category' => $category,
                'value' => $preferenceValue,
                'timestamp' => $timestamp,
            ]);
        }
    }
}
