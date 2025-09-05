<?php

namespace App\Http\Controllers;

use App\Traits\JsonResponse;
use App\Traits\LogContext;
use App\Traits\WaApiMetaWebhook;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WaApiController extends Controller
{
    use JsonResponse, LogContext, WaApiMetaWebhook;

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
            Log::info('WEBHOOK VERIFIED', $this->getLogContext($request, null));

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
        Log::debug("Webhook received {$timestamp}", $this->getLogContext($request, null, ['data' => json_encode($requestData, JSON_PRETTY_PRINT)]));

        $response = $this->processWebhookMessages($requestData);
        if ($response === true) {
            return $this->jsonSuccess('Webhook Received', 'Webhook data processed successfully');
        } else {
            return $this->jsonFailed('Webhook Processing Failed', 'Failed to process webhook data');
        }
    }
}
