<?php

namespace App\Http\Controllers;

use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\JsonResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WaApiController extends Controller
{
    use JsonResponse;

    /**
     * Handle the GET request for WhatsApp webhook verification.
     *
     * This method is used by WhatsApp to verify your webhook.
     */
    public function whatsappWebhookGet(Request $request): Response
    {
        if (! config('services.whatsapp.enabled')) {
            return $this->jsonFailed('WhatsApp API Disabled', 'WhatsApp API is currently disabled.');
        }

        $mode = $request->query('hub_mode');
        $challenge = $request->query('hub_challenge');
        $token = $request->query('hub_verify_token');
        $verifyToken = config('services.whatsapp.verify_token');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WEBHOOK VERIFIED');

            return response($challenge, 200);
        } else {
            return response('', 403);
        }
    }

    /**
     * Handle the POST request for incoming WhatsApp webhook data.
     *
     * This method processes incoming webhook data from WhatsApp.
     */
    public function whatsappWebhookPost(Request $request, string $veriId): HttpJsonResponse
    {
        if (! config('services.whatsapp.enabled')) {
            return $this->jsonFailed('WhatsApp API Disabled', 'WhatsApp API is currently disabled.');
        }

        /** Skip Webhook processing if veriId doesn't match, but return 200 to prevent guessing attack */
        if ($veriId !== config('services.whatsapp.veriId')) {
            return response()->json();
        }

        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $requestData = $request->all();
        Log::info("Webhook received {$timestamp}", ['data' => json_encode($requestData, JSON_PRETTY_PRINT)]);

        try {
            // Process webhook data if it contains messages
            $entry = $requestData['entry'][0] ?? null;
            if ($entry && isset($entry['changes'][0]['field']) && $entry['changes'][0]['field'] === 'messages') {
                $value = $entry['changes'][0]['value'];

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
                    WaMessageWebhookLog::create([
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
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing WhatsApp webhook: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return $this->jsonSuccess('Webhook Received', 'Webhook data processed successfully');
    }
}
