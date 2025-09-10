<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trait IpaymuFunction
 *
 * Helper for iPaymu v2 integration (JSON body flow).
 */
trait IpaymuFunction
{
    /**
     * Generate iPaymu signature for v2 API.
     *
     * @param  array  $body  Request body as associative array
     * @param  string  $va  Virtual account / merchant id
     * @param  string  $apiKey  API key
     * @param  string  $method  HTTP method (POST)
     * @return array ['signature'=>..., 'timestamp'=>...]
     */
    protected function generateIpaymuSignature(array $body, string $va, string $apiKey, string $method = 'POST'): array
    {
        $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
        $requestBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = strtoupper($method).':'.$va.':'.$requestBody.':'.$apiKey;
        $signature = hash_hmac('sha256', $stringToSign, $apiKey);
        $timestamp = date('YmdHis');

        return ['signature' => $signature, 'timestamp' => $timestamp, 'json_body' => $jsonBody];
    }

    /**
     * Create payment via iPaymu API (JSON flow). Returns decoded response.
     *
     * @param  array  $body  Request body array expected by iPaymu (product[], qty[], price[], returnUrl, notifyUrl, cancelUrl, referenceId, etc.)
     *
     * Sample success response:
     * {
     *   "Status": 200,
     *   "Success": true,
     *   "Message": "Success",
     *   "Data": {
     *     "SessionID": "2bef2a9a-c9c9-4df7-ab1d-eb551adc1d0c",
     *     "Url": "https:\/\/sandbox-payment.ipaymu.com\/#\/2bef2a9a-c9c9-4df7-ab1d-eb551adc1d0c"
     *   }
     * }
     * @return array|null
     */
    public function createIpaymuPayment(array $body)
    {
        $cfg = config('services.ipaymu');
        if (! isset($cfg['enabled']) || ! $cfg['enabled']) {
            return null;
        }

        $va = $cfg['va'] ?? null;
        $apiKey = $cfg['api_key'] ?? null;
        $isProd = $cfg['production'] ?? false;
        $base = $isProd ? ($cfg['api_prod'] ?? 'https://my.ipaymu.com') : ($cfg['api_sandbox'] ?? 'https://sandbox.ipaymu.com');
        $url = rtrim($base, '/').'/api/v2/payment';

        if (empty($va) || empty($apiKey)) {
            throw new \RuntimeException('iPaymu VA or API key not configured.');
        }

        $sig = $this->generateIpaymuSignature($body, $va, $apiKey, 'POST');

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'va' => $va,
            'signature' => $sig['signature'],
            'timestamp' => $sig['timestamp'],
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        if ($response->successful()) {
            $data = $response->json();
            // Log success details if present
            $sessionId = $data['Data']['SessionID'] ?? null;
            $urlResult = $data['Data']['Url'] ?? ($data['url'] ?? null);
            $reference = $body['referenceId'] ?? null;
            Log::info('iPaymu payment created', [
                'referenceId' => $reference,
                'sessionId' => $sessionId,
                'url' => $urlResult,
                'response' => $data,
            ]);

            return $data;
        }

        $error = [
            'status' => 'error',
            'http_status' => $response->status(),
            'body' => $response->body(),
        ];

        Log::error('iPaymu payment error', array_merge(['referenceId' => $body['referenceId'] ?? null], $error));

        return $error;
    }

    /**
     * Handle iPaymu webhook notification (POST JSON body).
     * Accepts Illuminate\Http\Request or an array.
     *
     * Sample body:
     * {"trx_id":177878,"sid":"2bef2a9a-c9c9-4df7-ab1d-eb551adc1d0c","reference_id":"TEST-1757496378","status":"berhasil","status_code":1,...}
     *
     * @param  mixed  $request
     * @return array Normalized notification data
     */
    public function handleIpaymuNotification($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $body = $request->all();
        } elseif (is_array($request)) {
            $body = $request;
        } else {
            $body = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        $status = $body['status'] ?? null; // e.g. 'berhasil'
        $statusCode = isset($body['status_code']) ? (int) $body['status_code'] : null; // 1 = success
        $referenceId = $body['reference_id'] ?? $body['referenceId'] ?? null;
        $trxId = $body['trx_id'] ?? null;
        $amount = $body['amount'] ?? $body['total'] ?? $body['paid_off'] ?? null;
        $paidAt = $body['paid_at'] ?? null;
        $settlementStatus = $body['settlement_status'] ?? null;
        $buyerName = $body['buyer_name'] ?? null;
        $buyerEmail = $body['buyer_email'] ?? null;

        // Normalize status to simple keywords
        $normalized = 'unknown';
        // Many iPaymu sandbox examples use 'berhasil' for success
        if (in_array(strtolower((string) $status), ['berhasil', 'success', 'settled'], true) || $statusCode === 1 || strtolower((string) $settlementStatus) === 'settled') {
            $normalized = 'paid';
        } elseif (in_array(strtolower((string) $status), ['pending', 'menunggu'], true) || $statusCode === 0) {
            $normalized = 'pending';
        } elseif (in_array(strtolower((string) $status), ['gagal', 'failed', 'cancelled', 'cancel'], true)) {
            $normalized = 'failed';
        }

        $result = [
            'trx_id' => $trxId,
            'reference_id' => $referenceId,
            'status' => $status,
            'status_code' => $statusCode,
            'normalized_status' => $normalized,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'settlement_status' => $settlementStatus,
            'buyer_name' => $buyerName,
            'buyer_email' => $buyerEmail,
            'raw' => $body,
        ];

        // Log and return
        if ($normalized === 'paid') {
            Log::info('iPaymu notification: paid', ['reference_id' => $referenceId, 'trx_id' => $trxId, 'amount' => $amount]);
        } else {
            Log::info('iPaymu notification received', ['reference_id' => $referenceId, 'status' => $status]);
        }

        return $result;
    }

    /**
     * Capture redirect URL query parameters from iPaymu (when buyer is redirected back).
     * Example:
     * https://example.com/return?return=true&sid=b59622de-467d-4ef5-adf8-e2bca3e11395&trx_id=177881&status=berhasil&tipe=cstore&payment_method=cstore&payment_channel=alfamart
     *
     * @param  mixed  $request
     * @return array Normalized callback data
     */
    public function captureIpaymuRedirectData($request): array
    {
        if ($request instanceof \Illuminate\Http\Request) {
            $params = $request->query();
        } elseif (is_array($request)) {
            $params = $request;
        } else {
            $params = method_exists($request, 'toArray') ? $request->toArray() : (array) $request;
        }

        return $this->normalizeIpaymuCallbackParams($params);
    }

    /**
     * Normalize callback params for iPaymu redirect.
     */
    protected function normalizeIpaymuCallbackParams(array $params): array
    {
        $sid = $params['sid'] ?? null;
        $trxId = $params['trx_id'] ?? null;
        $referenceId = $params['reference_id'] ?? $params['referenceId'] ?? null;
        $status = $params['status'] ?? null;
        $type = $params['tipe'] ?? $params['type'] ?? null;
        $paymentMethod = $params['payment_method'] ?? null;
        $paymentChannel = $params['payment_channel'] ?? null;

        $normalized = [
            'sid' => $sid,
            'trx_id' => $trxId,
            'reference_id' => $referenceId,
            'status' => $status,
            'type' => $type,
            'payment_method' => $paymentMethod,
            'payment_channel' => $paymentChannel,
            'raw' => $params,
        ];

        // Map status
        if (in_array(strtolower((string) $status), ['berhasil', 'success'], true)) {
            $normalized['status_short'] = 'paid';
        } elseif (in_array(strtolower((string) $status), ['pending', 'menunggu'], true)) {
            $normalized['status_short'] = 'pending';
        } else {
            $normalized['status_short'] = 'unknown';
        }

        Log::info('iPaymu redirect captured', ['reference_id' => $referenceId, 'status' => $normalized['status_short']]);

        return $normalized;
    }
}
