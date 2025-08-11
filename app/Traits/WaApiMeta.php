<?php

namespace App\Traits;

use App\Models\WaApiMeta\WaMessageSentLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WaApiMeta
{
    /**
     * Send a WhatsApp message using the Meta Cloud API
     *
     * @param  string  $to  The recipient's phone number with country code (no + or spaces)
     * @param  string  $message  The message body text to send
     * @param  bool  $previewUrl  Whether to generate URL previews in the message (default: false)
     * @return array|null The API response or null on error
     */
    protected function sendWhatsAppMessage(string $to, string $message, bool $previewUrl = false): ?array
    {
        // Create log entry data
        $logData = [
            'recipient_number' => $to,
            'message_content' => $message,
            'preview_url' => $previewUrl,
            'success' => false,
        ];

        if (! config('services.whatsapp.enabled')) {
            Log::warning('WhatsApp API is disabled. Message not sent.');

            // Log the failed attempt with reason
            $logData['error_data'] = ['reason' => 'WhatsApp API is disabled'];
            WaMessageSentLog::create($logData);

            return null;
        }

        $endpoint = config('services.whatsapp.endpoint');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $accessToken = config('services.whatsapp.access_token');

        if (! $endpoint || ! $phoneNumberId || ! $accessToken) {
            Log::error('WhatsApp API configuration missing. Check your .env file.');

            // Log the failed attempt with reason
            $logData['error_data'] = ['reason' => 'WhatsApp API configuration missing'];
            WaMessageSentLog::create($logData);

            return null;
        }

        $url = "{$endpoint}{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => $previewUrl,
                'body' => $message,
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? null;

                Log::info('WhatsApp message sent successfully', [
                    'to' => $to,
                    'message_id' => $messageId,
                ]);

                // Log the successful message
                $logData['message_id'] = $messageId;
                $logData['success'] = true;
                $logData['response_data'] = $responseData;
                WaMessageSentLog::create($logData);

                return $responseData;
            } else {
                $errorData = [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ];

                Log::error('WhatsApp API error', $errorData);

                // Log the failed attempt with error details
                $logData['error_data'] = $errorData;
                WaMessageSentLog::create($logData);

                return null;
            }
        } catch (\Exception $e) {
            $errorData = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error('WhatsApp API exception: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            // Log the failed attempt with exception details
            $logData['error_data'] = $errorData;
            WaMessageSentLog::create($logData);

            return null;
        }
    }

    /**
     * Add phone number to AI exception reply
     * For now set to 30 minutes
     */
    public function addToAIExceptionReply(string $phoneNumber): void
    {
        Cache::add("ai_exception_reply:{$phoneNumber}", true, 1800);

        Log::debug('Added phone number to AI exception reply', ['phone_number' => $phoneNumber]);
    }
}
