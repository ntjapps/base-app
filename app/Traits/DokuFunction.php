<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trait DokuFunction
 *
 * Helper for Doku Checkout integration.
 */
trait DokuFunction
{
    /**
     * Generate Doku signature from a pre-encoded JSON body string.
     *
     * @param  string  $jsonBody  The minified JSON request body string.
     * @param  string  $clientId  Client ID
     * @param  string  $requestId  Unique request ID
     * @param  string  $requestTimestamp  ISO8601 timestamp
     * @param  string  $secretKey  Secret key
     * @param  string  $requestTarget  Request target path (e.g., /checkout/v1/payment)
     * @return string HMACSHA256 signature
     */
    protected function generateDokuSignature(string $jsonBody, string $clientId, string $requestId, string $requestTimestamp, string $secretKey, string $requestTarget): string
    {
        // Generate Digest: SHA256 base64 hash of the provided JSON body string
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
    public function createDokuPayment(array $body, string $requestId)
    {
        $cfg = config('services.doku');
        if (! isset($cfg['enabled']) || ! $cfg['enabled']) {
            return null;
        }

        $clientId = $cfg['client_id'] ?? null;
        $secretKey = $cfg['secret_key'] ?? null;
        $isProd = $cfg['production'] ?? false;
        $base = $isProd ? ($cfg['api_prod'] ?? 'https://api.doku.com') : ($cfg['api_sandbox'] ?? 'https://api-sandbox.doku.com');
        $requestTarget = '/checkout/v1/payment';
        $url = rtrim($base, '/').$requestTarget;

        if (empty($clientId) || empty($secretKey)) {
            throw new \RuntimeException('Doku Client ID or Secret Key not configured.');
        }

        // 1. Encode the body to a JSON string first. This string will be used for BOTH
        //    the signature digest and the actual request body. This is the crucial fix.
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

        Log::debug('Doku create payment request', ['url' => $url, 'body' => $jsonBody]);

        $requestTimestamp = Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z');

        $signature = $this->generateDokuSignature($jsonBody, $clientId, $requestId, $requestTimestamp, $secretKey, $requestTarget);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Client-Id' => $clientId,
            'Request-Id' => $requestId,
            'Request-Timestamp' => $requestTimestamp,
            'Signature' => 'HMACSHA256='.$signature,
        ];

        // 2. Send the pre-encoded JSON string in the request body using withBody().
        $response = Http::withHeaders($headers)
            ->withBody($jsonBody, 'application/json')
            ->post($url);

        if ($response->successful()) {
            $data = $response->json();
            Log::info('Doku payment created', [
                'invoice_number' => $data['response']['order']['invoice_number'] ?? null,
                'payment_url' => $data['response']['payment']['url'] ?? null,
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
     * @param  string  $jsonBody  Raw JSON body from Doku webhook
     * @param  string  $clientId  Client ID from headers
     * @param  string  $requestId  Request ID from headers
     * @param  string  $requestTimestamp  Request timestamp from headers
     * @param  string  $providedSignature  Signature from headers
     * @param  string  $secretKey  Secret key
     * @param  string  $requestTarget  Request target path for notifications
     */
    public function verifyDokuNotificationSignature(string $jsonBody, string $clientId, string $requestId, string $requestTimestamp, string $providedSignature, string $secretKey, string $requestTarget = '/payments/notifications'): bool
    {
        $computedSignatureValue = $this->generateDokuSignature($jsonBody, $clientId, $requestId, $requestTimestamp, $secretKey, $requestTarget);
        $expectedSignature = 'HMACSHA256='.$computedSignatureValue;

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
     *
     * @param  array  $headers  Optional request headers override
     * @return array Normalized notification data
     */
    public function handleDokuNotification(Request $request, array $headers = []): array
    {
        // 1. Get the RAW content of the request. Do not use ->all() or ->json() yet.
        // This is crucial for verifying the signature against the original payload.
        $jsonBody = $request->getContent();
        $body = json_decode($jsonBody, true) ?: [];

        $headers = $headers ?: [
            'client_id' => $request->header('Client-Id'),
            'request_id' => $request->header('Request-Id'),
            'request_timestamp' => $request->header('Request-Timestamp'),
            'signature' => $request->header('Signature'),
        ];

        $cfg = config('services.doku');
        $secretKey = $cfg['secret_key'] ?? null;
        $clientId = $headers['client_id'] ?? $cfg['client_id'] ?? null;

        $isValid = false;
        if ($secretKey && $clientId && isset($headers['request_id'], $headers['request_timestamp'], $headers['signature'])) {
            $isValid = $this->verifyDokuNotificationSignature(
                $jsonBody, // 2. Verify against the raw JSON string
                $clientId,
                $headers['request_id'],
                $headers['request_timestamp'],
                $headers['signature'],
                $secretKey
            );
        }

        $transactionStatus = $body['transaction']['status'] ?? null;
        $invoiceNumber = $body['order']['invoice_number'] ?? null;

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
            'amount' => $body['order']['amount'] ?? null,
            'transaction_status' => $transactionStatus,
            'service_id' => $body['service']['id'] ?? null,
            'channel_id' => $body['channel']['id'] ?? null,
            'transaction_date' => $body['transaction']['date'] ?? null,
            'normalized_status' => $normalized,
            'signature_valid' => $isValid,
            'raw' => $body,
        ];

        // Log based on signature validity
        $logContext = [
            'invoice_number' => $invoiceNumber,
            'status' => $normalized,
            'signature_valid' => $isValid,
        ];

        if ($isValid) {
            Log::info('Doku notification processed', $logContext);
        } else {
            Log::warning('Doku notification processed with invalid signature', $logContext);
        }

        return $result;
    }

    // ... The redirect capture methods can remain the same as they don't involve signatures ...
    public function captureDokuRedirectData($request): array
    {
        if ($request instanceof Request) {
            $params = $request->query();
        } elseif (is_array($request)) {
            $params = $request;
        } else {
            $params = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        return $this->normalizeDokuCallbackParams($params);
    }

    /**
     * Normalize Doku create-payment API response into a consistent structure.
     *
     * Accepts either a JSON string, an array, or an object (decoded JSON), and
     * returns an associative array with common fields used by the application.
     *
     * Expected input shape (example):
     * {
     *   "message": ["SUCCESS"],
     *   "response": {
     *     "order": { "amount": "80000", "invoice_number": "INV...", "session_id": "..." },
     *     "payment": { "url": "...", "token_id": "...", "expired_date": "...", "expired_datetime": "..." },
     *     "headers": { "request_id": "...", "signature": "...", "date": "...", "client_id": "..." }
     *   }
     * }
     *
     * @param  mixed  $response  JSON string or decoded array/object
     * @return array Normalized data
     */
    public function captureDokuCreateResponse($response): array
    {
        if (is_string($response)) {
            $decoded = json_decode($response, true) ?: [];
        } elseif (is_object($response)) {
            $decoded = json_decode(json_encode($response), true) ?: [];
        } elseif (is_array($response)) {
            $decoded = $response;
        } else {
            $decoded = [];
        }

        $resp = $decoded['response'] ?? [];
        $order = $resp['order'] ?? [];
        $payment = $resp['payment'] ?? [];
        $headers = $resp['headers'] ?? [];

        $invoice = $order['invoice_number'] ?? null;
        $amount = isset($order['amount']) ? (int) $order['amount'] : (isset($payment['amount']) ? (int) $payment['amount'] : null);
        // payment url may be nested under payment.url
        $paymentUrl = $payment['url'] ?? $resp['payment_url'] ?? null;
        $tokenId = $payment['token_id'] ?? null;

        $normalized = [
            'invoice_number' => $invoice,
            'amount' => $amount,
            'payment_url' => $paymentUrl,
            'token_id' => $tokenId,
            'expired_date' => $payment['expired_date'] ?? null,
            'expired_datetime' => $payment['expired_datetime'] ?? null,
            'session_id' => $order['session_id'] ?? null,
            'headers' => [
                'request_id' => $headers['request_id'] ?? $decoded['response']['headers']['request_id'] ?? null,
                'signature' => $headers['signature'] ?? $decoded['response']['headers']['signature'] ?? null,
                'date' => $headers['date'] ?? $decoded['response']['headers']['date'] ?? null,
                'client_id' => $headers['client_id'] ?? $decoded['response']['headers']['client_id'] ?? null,
            ],
            'raw' => $decoded,
            'status' => (is_array($decoded['message'] ?? null) ? implode(',', $decoded['message']) : ($decoded['message'] ?? null)),
        ];

        Log::info('Doku create response captured', [
            'invoice_number' => $normalized['invoice_number'],
            'payment_url' => $normalized['payment_url'],
        ]);

        return $normalized;
    }

    protected function normalizeDokuCallbackParams(array $params): array
    {
        $invoiceNumber = $params['invoice_number'] ?? null;
        $status = $params['status'] ?? null;
        $transactionStatus = $params['transaction_status'] ?? null;

        $normalized = [
            'invoice_number' => $invoiceNumber,
            'status' => $status,
            'transaction_status' => $transactionStatus,
            'session_id' => $params['session_id'] ?? null,
            'raw' => $params,
            'status_short' => 'unknown',
        ];

        if (strtoupper((string) $status) === 'SUCCESS' || strtoupper((string) $transactionStatus) === 'SUCCESS') {
            $normalized['status_short'] = 'paid';
        } elseif (strtoupper((string) $status) === 'FAILED' || strtoupper((string) $transactionStatus) === 'FAILED') {
            $normalized['status_short'] = 'failed';
        }

        Log::info('Doku redirect captured', [
            'invoice_number' => $invoiceNumber,
            'status' => $normalized['status_short'],
        ]);

        return $normalized;
    }
}
