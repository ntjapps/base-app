<?php

namespace App\Traits;

use App\Interfaces\GoQueues;
use Illuminate\Support\Facades\Log;

trait WaApiMetaWebhook
{
    use GoWorkerFunction;

    public function processWebhookMessages(array $requestData): bool
    {
        // Dispatch the raw webhook payload to the Go worker immediately.
        // The Go worker handles parsing, logging to DB, and processing.
        try {
            $this->sendGoTask('wa-inbound', $requestData, GoQueues::WHATSAPP);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch WhatsApp webhook to Go worker: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return false;
        }

        return true;
    }

    // Legacy PHP processing methods removed: inbound parsing is now handled by the Go worker (task `wa-inbound`).
    // The original helper methods (message parsing, account alerts, settings, and message status handlers)
    // were deprecated and have been deleted to avoid duplication with the Go worker implementation.

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
