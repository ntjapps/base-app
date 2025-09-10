<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Use fully-qualified facade calls below to avoid namespace resolution issues

/**
 * Trait MidtransFunction
 *
 * Minimal helper for Midtrans Snap integration.
 * Provides a method to create a Snap transaction using server key authentication.
 *
 * Usage:
 *  use App\Traits\MidtransFunction;
 *  $this->createSnapTransaction($orderId, $amount, $customerDetails, $items);
 */
trait MidtransFunction
{
    /**
     * Create a Midtrans Snap transaction and return the API response.
     *
     * @param  int|float  $grossAmount
     * @param  array  $customerDetails  (e.g. ['first_name'=>'John','last_name'=>'Doe','email'=>'john@example.com','phone'=>'0812...'])
     * @param  array  $items  array of item details as Midtrans expects
     * @param  array  $options  additional params to merge into payload
     * @return array|null
     */
    public function createSnapTransaction(string $orderId, $grossAmount, array $customerDetails = [], array $items = [], array $options = [])
    {
        $cfg = config('services.midtrans');
        if (! isset($cfg['enabled']) || ! $cfg['enabled']) {
            return null;
        }

        $serverKey = $cfg['server_key'] ?? null;
        // Determine endpoint based on production flag
        $isProd = $cfg['production'] ?? false;
        $apiSandbox = $cfg['api_sandbox'] ?? 'https://app.sandbox.midtrans.com/snap/v1/transactions';
        $apiProd = $cfg['api_prod'] ?? 'https://app.midtrans.com/snap/v1/transactions';
        $url = $isProd ? $apiProd : $apiSandbox;

        if (empty($serverKey)) {
            throw new \RuntimeException('Midtrans server key not configured.');
        }

        // Base payload
        $basePayload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (float) $grossAmount,
            ],
            'customer_details' => $customerDetails,
            'item_details' => $items,
        ];

        // Optional: default callbacks.finish from config if provided
        if (! empty($cfg['callbacks']['finish']) && \filter_var($cfg['callbacks']['finish'], FILTER_VALIDATE_URL)) {
            $basePayload['callbacks'] = [
                'finish' => $cfg['callbacks']['finish'],
            ];
        }

        // Merge user-provided options last, to allow overriding config defaults
        $payload = array_merge($basePayload, $options);

        // Midtrans requires Basic auth with server key as username and empty password
        $authHeader = 'Basic '.base64_encode($serverKey.':');

        $response = Http::withHeaders([
            'Authorization' => $authHeader,
            'Accept' => 'application/json',
        ])->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            Log::info('Midtrans SNAP created', [
                'order_id' => $orderId,
                'token' => $data['token'] ?? null,
                'redirect_url' => $data['redirect_url'] ?? null,
            ]);

            return $data;
        }

        // For debugging, include body and status and log error
        $error = [
            'status' => 'error',
            'http_status' => $response->status(),
            'body' => $response->body(),
        ];

        Log::error('Midtrans SNAP error', array_merge(['order_id' => $orderId], $error));

        return $error;
    }

    /**
     * Verify Midtrans notification signature.
     * Signature formula (per Midtrans docs):
     * sha512(order_id + status_code + gross_amount + server_key)
     *
     * @param  array  $body  Parsed JSON body from Midtrans webhook
     */
    public function verifyNotificationSignature(array $body): bool
    {
        $cfg = config('services.midtrans');
        $serverKey = $cfg['server_key'] ?? null;
        if (! $serverKey) {
            Log::warning('Midtrans signature verification skipped: missing server key');

            return false;
        }

        $orderId = (string) ($body['order_id'] ?? '');
        $statusCode = (string) ($body['status_code'] ?? '');
        // gross_amount should be exactly as sent by Midtrans (string with decimals)
        $grossAmount = (string) ($body['gross_amount'] ?? '');
        $providedSignature = (string) ($body['signature_key'] ?? '');

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $providedSignature === '') {
            return false;
        }

        $computed = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $ok = hash_equals($computed, $providedSignature);

        if (! $ok) {
            Log::warning('Midtrans signature mismatch', [
                'order_id' => $orderId,
                'status_code' => $statusCode,
            ]);
        }

        return $ok;
    }

    /**
     * Handle Midtrans notification webhook (server-to-server POST).
     * Accepts Illuminate\Http\Request or array body.
     *
     * Returns normalized info including signature validity and a concise status.
     *
     * @param  mixed  $request
     */
    public function handleNotification($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            // Laravel automatically parses JSON into an array via ->all()
            $body = $request->all();
        } elseif (is_array($request)) {
            $body = $request;
        } else {
            $body = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        $isValid = $this->verifyNotificationSignature($body);

        $transactionStatus = $body['transaction_status'] ?? null;
        $fraudStatus = $body['fraud_status'] ?? null;
        $paymentType = $body['payment_type'] ?? null;
        $statusCode = isset($body['status_code']) ? (int) $body['status_code'] : null;
        $orderId = $body['order_id'] ?? null;
        $grossAmount = $body['gross_amount'] ?? null; // keep as string

        // Normalize to application-level status
        $normalized = 'unknown';
        switch ($transactionStatus) {
            case 'capture':
                if ($paymentType === 'credit_card') {
                    if ($fraudStatus === 'challenge') {
                        $normalized = 'challenge';
                    } elseif ($fraudStatus === 'accept' || $fraudStatus === null) {
                        $normalized = 'paid';
                    } else {
                        $normalized = 'review';
                    }
                } else {
                    $normalized = 'paid';
                }
                break;
            case 'settlement':
                $normalized = 'paid';
                break;
            case 'pending':
                $normalized = 'pending';
                break;
            case 'deny':
            case 'cancel':
                $normalized = 'failed';
                break;
            case 'expire':
                $normalized = 'expired';
                break;
            case 'refund':
                $normalized = 'refunded';
                break;
            case 'partial_refund':
                $normalized = 'partial_refunded';
                break;
            case 'chargeback':
                $normalized = 'chargeback';
                break;
        }

        $result = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
            'payment_type' => $paymentType,
            'fraud_status' => $fraudStatus,
            'gross_amount' => $grossAmount,
            'signature_valid' => $isValid,
            'status' => $normalized,
            'raw' => $body,
        ];

        $logCtx = ['order_id' => $orderId, 'status' => $normalized, 'signature_valid' => $isValid];
        if ($isValid) {
            Log::info('Midtrans notification processed', $logCtx);
        } else {
            Log::warning('Midtrans notification processed with invalid signature', $logCtx);
        }

        return $result;
    }

    /**
     * Capture redirect URL query parameters from Midtrans (when user is redirected back).
     * Accepts an Illuminate\Http\Request or an array of query params.
     *
     * Example redirect URL:
     *  https://example.com/?order_id=TEST-1757494063&status_code=200&transaction_status=capture
     *
     * @param  mixed  $request
     * @return array Normalized callback data
     */
    public function captureRedirectData($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $params = $request->query();
        } elseif (is_array($request)) {
            $params = $request;
        } else {
            if (method_exists($request, 'toArray')) {
                $params = $request->toArray();
            } else {
                $params = (array) $request;
            }
        }

        return $this->normalizeMidtransCallbackParams($params);
    }

    /**
     * Normalize common Midtrans callback/redirect parameters into a stable shape.
     */
    protected function normalizeMidtransCallbackParams(array $params): array
    {
        $orderId = $params['order_id'] ?? null;
        $statusCode = isset($params['status_code']) ? (int) $params['status_code'] : null;
        $transactionStatus = $params['transaction_status'] ?? $params['transaction_status'] ?? null;

        $normalized = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'transaction_status' => $transactionStatus,
            'raw' => $params,
        ];

        // Map to short status
        switch ($transactionStatus) {
            case 'settlement':
            case 'capture':
                $normalized['status'] = 'paid';
                break;
            case 'pending':
                $normalized['status'] = 'pending';
                break;
            case 'deny':
            case 'cancel':
            case 'expire':
            case 'failure':
                $normalized['status'] = 'failed';
                break;
            default:
                $normalized['status'] = 'unknown';
        }

        Log::info('Midtrans redirect captured', ['order_id' => $orderId, 'status' => $normalized['status']]);

        return $normalized;
    }
}
