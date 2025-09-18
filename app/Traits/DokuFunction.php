<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Trait DokuFunction
 *
 * Helper for Doku Checkout integration.
 */
trait DokuFunction
{
    /**
     * Generate Doku signature for API requests.
     *
     * @param  array  $body  Request body as associative array
     * @param  string  $clientId  Client ID
     * @param  string  $requestId  Unique request ID
     * @param  string  $requestTimestamp  ISO8601 timestamp
     * @param  string  $secretKey  Secret key
     * @param  string  $requestTarget  Request target path (e.g., /checkout/v1/payment)
     * @return string HMACSHA256 signature
     */
    protected function generateDokuSignature(array $body, string $clientId, string $requestId, string $requestTimestamp, string $secretKey, string $requestTarget = '/checkout/v1/payment'): string
    {
        // Generate Digest: SHA256 base64 hash of JSON body
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        // Create string to sign
        $stringToSign = "Client-Id:{$clientId}\n".
                       "Request-Id:{$requestId}\n".
                       "Request-Timestamp:{$requestTimestamp}\n".
                       "Request-Target:{$requestTarget}\n".
                       "Digest:{$digest}";

        // Calculate HMAC-SHA256 base64
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));

        return $signature;
    }

    /**
     * Create payment via Doku Checkout API. Returns decoded response.
     *
     * @param  array  $body  Request body array (order, payment, customer, etc.)
     * @return array|null
     */
    public function createDokuPayment(array $body)
    {
        $cfg = config('services.doku');
        if (! isset($cfg['enabled']) || ! $cfg['enabled']) {
            return null;
        }

        $clientId = $cfg['client_id'] ?? null;
        $secretKey = $cfg['secret_key'] ?? null;
        $isProd = $cfg['production'] ?? false;
        $base = $isProd ? ($cfg['api_prod'] ?? 'https://api.doku.com') : ($cfg['api_sandbox'] ?? 'https://api-sandbox.doku.com');
        $url = rtrim($base, '/').'/checkout/v1/payment';

        if (empty($clientId) || empty($secretKey)) {
            throw new \RuntimeException('Doku Client ID or Secret Key not configured.');
        }

        $requestId = uniqid('req_', true);
        $requestTimestamp = Carbon::now('UTC')->toISOString(); // ISO8601 UTC+0

        $signature = $this->generateDokuSignature($body, $clientId, $requestId, $requestTimestamp, $secretKey);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $requestTimestamp,
            'Signature' => 'HMACSHA256='.$signature,
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            // Log success details if present
            $invoiceNumber = $data['response']['order']['invoice_number'] ?? null;
            $sessionId = $data['response']['order']['session_id'] ?? null;
            $paymentUrl = $data['response']['payment']['url'] ?? null;
            $tokenId = $data['response']['payment']['token_id'] ?? null;

            Log::info('Doku payment created', [
                'invoice_number' => $invoiceNumber,
                'session_id' => $sessionId,
                'payment_url' => $paymentUrl,
                'token_id' => $tokenId,
                'response' => $data,
            ]);

            return $data;
        }

        $error = [
            'status' => 'error',
            'http_status' => $response->status(),
            'body' => $response->body(),
        ];

        Log::error('Doku payment error', array_merge(['invoice_number' => $body['order']['invoice_number'] ?? null], $error));

        return $error;
    }

    /**
     * Verify Doku notification signature.
     *
     * @param  array  $body  Parsed JSON body from Doku webhook
     * @param  string  $clientId  Client ID from headers
     * @param  string  $requestId  Request ID from headers
     * @param  string  $requestTimestamp  Request timestamp from headers
     * @param  string  $providedSignature  Signature from headers
     * @param  string  $secretKey  Secret key
     * @param  string  $requestTarget  Request target path for notifications
     */
    public function verifyDokuNotificationSignature(array $body, string $clientId, string $requestId, string $requestTimestamp, string $providedSignature, string $secretKey, string $requestTarget = '/payments/notifications'): bool
    {
        // Generate Digest: SHA256 base64 hash of JSON body
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $digest = base64_encode(hash('sha256', $jsonBody, true));

        // Create string to sign
        $stringToSign = "Client-Id:{$clientId}\n".
                       "Request-Id:{$requestId}\n".
                       "Request-Timestamp:{$requestTimestamp}\n".
                       "Request-Target:{$requestTarget}\n".
                       "Digest:{$digest}";

        // Calculate HMAC-SHA256 base64
        $computed = base64_encode(hash_hmac('sha256', $stringToSign, $secretKey, true));
        $expectedSignature = 'HMACSHA256='.$computed;

        $ok = hash_equals($expectedSignature, $providedSignature);

        if (! $ok) {
            Log::warning('Doku signature mismatch', [
                'request_id' => $requestId,
                'expected' => $expectedSignature,
                'provided' => $providedSignature,
            ]);
        }

        return $ok;
    }

    /**
     * Handle Doku notification webhook (POST JSON body).
     * Accepts Illuminate\Http\Request or an array.
     *
     * Sample body:
     * {
     *   "service": {"id": "VIRTUAL_ACCOUNT"},
     *   "acquirer": {"id": "BCA"},
     *   "channel": {"id": "VIRTUAL_ACCOUNT_BCA"},
     *   "transaction": {"status": "SUCCESS", "date": "2021-01-27T03:24:23Z"},
     *   "order": {"invoice_number": "INV-20210124-0001", "amount": 150000},
     *   "virtual_account_info": {"virtual_account_number": "1900600000000046"}
     * }
     *
     * @param  mixed  $request
     * @param  array  $headers  Request headers (Client-Id, Request-Id, Request-Timestamp, Signature)
     * @return array Normalized notification data
     */
    public function handleDokuNotification($request, array $headers = []): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $body = $request->all();
            $headers = $headers ?: [
                'client_id' => $request->header('Client-Id'),
                'request_id' => $request->header('Request-Id'),
                'request_timestamp' => $request->header('Request-Timestamp'),
                'signature' => $request->header('Signature'),
            ];
        } elseif (is_array($request)) {
            $body = $request;
        } else {
            $body = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        $cfg = config('services.doku');
        $secretKey = $cfg['secret_key'] ?? null;
        $clientId = $headers['client_id'] ?? $cfg['client_id'] ?? null;

        $isValid = false;
        if ($secretKey && $clientId && isset($headers['request_id'], $headers['request_timestamp'], $headers['signature'])) {
            $isValid = $this->verifyDokuNotificationSignature(
                $body,
                $clientId,
                $headers['request_id'],
                $headers['request_timestamp'],
                $headers['signature'],
                $secretKey
            );
        }

        $transactionStatus = $body['transaction']['status'] ?? null;
        $serviceId = $body['service']['id'] ?? null;
        $channelId = $body['channel']['id'] ?? null;
        $invoiceNumber = $body['order']['invoice_number'] ?? null;
        $amount = $body['order']['amount'] ?? null;
        $transactionDate = $body['transaction']['date'] ?? null;

        // Normalize status
        $normalized = 'unknown';
        if (strtoupper((string) $transactionStatus) === 'SUCCESS') {
            $normalized = 'paid';
        } elseif (in_array(strtoupper((string) $transactionStatus), ['FAILED', 'DECLINE'], true)) {
            $normalized = 'failed';
        } elseif (in_array(strtoupper((string) $transactionStatus), ['PENDING', 'PROCESSING'], true)) {
            $normalized = 'pending';
        }

        $result = [
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'transaction_status' => $transactionStatus,
            'service_id' => $serviceId,
            'channel_id' => $channelId,
            'transaction_date' => $transactionDate,
            'normalized_status' => $normalized,
            'signature_valid' => $isValid,
            'raw' => $body,
        ];

        // Log
        if ($isValid) {
            Log::info('Doku notification processed', [
                'invoice_number' => $invoiceNumber,
                'status' => $normalized,
                'signature_valid' => $isValid,
            ]);
        } else {
            Log::warning('Doku notification processed with invalid signature', [
                'invoice_number' => $invoiceNumber,
                'status' => $normalized,
                'signature_valid' => $isValid,
            ]);
        }

        return $result;
    }

    /**
     * Capture redirect URL query parameters from Doku (when buyer is redirected back).
     * Example:
     * https://example.com/return?invoice_number=INV-20210231-0001&status=success&transaction_status=SUCCESS
     *
     * @param  mixed  $request
     * @return array Normalized callback data
     */
    public function captureDokuRedirectData($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $params = $request->query();
        } elseif (is_array($request)) {
            $params = $request;
        } else {
            $params = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        return $this->normalizeDokuCallbackParams($params);
    }

    /**
     * Normalize common Doku callback/redirect parameters into a stable shape.
     */
    protected function normalizeDokuCallbackParams(array $params): array
    {
        $invoiceNumber = $params['invoice_number'] ?? null;
        $status = $params['status'] ?? null;
        $transactionStatus = $params['transaction_status'] ?? null;
        $sessionId = $params['session_id'] ?? null;

        $normalized = [
            'invoice_number' => $invoiceNumber,
            'status' => $status,
            'transaction_status' => $transactionStatus,
            'session_id' => $sessionId,
            'raw' => $params,
        ];

        // Map status
        if (strtoupper((string) $status) === 'SUCCESS' || strtoupper((string) $transactionStatus) === 'SUCCESS') {
            $normalized['status_short'] = 'paid';
        } elseif (strtoupper((string) $status) === 'FAILED' || strtoupper((string) $transactionStatus) === 'FAILED') {
            $normalized['status_short'] = 'failed';
        } else {
            $normalized['status_short'] = 'unknown';
        }

        Log::info('Doku redirect captured', [
            'invoice_number' => $invoiceNumber,
            'status' => $normalized['status_short'],
        ]);

        return $normalized;
    }
}
