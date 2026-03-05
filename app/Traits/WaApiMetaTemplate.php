<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WaApiMetaTemplate
{
    /**
     * Validate WhatsApp API configuration and return credentials
     *
     * @return array{endpoint: string, waba_id: string, access_token: string}|null
     */
    private function getWhatsAppConfig(): ?array
    {
        if (! config('services.whatsapp.enabled')) {
            Log::warning('WhatsApp templates - API disabled.');

            return null;
        }

        $endpoint = config('services.whatsapp.endpoint');
        $wabaId = config('services.whatsapp.business_id');
        $accessToken = config('services.whatsapp.access_token');

        if (! $endpoint || ! $wabaId || ! $accessToken) {
            Log::error('WhatsApp templates - configuration missing.');

            return null;
        }

        return [
            'endpoint' => $endpoint,
            'waba_id' => $wabaId,
            'access_token' => $accessToken,
        ];
    }

    /**
     * Get list of templates for a WABA
     * GET /<WABA_ID>/message_templates?fields=<fields>&limit=<limit>
     */
    protected function getTemplates(array $fields = [
        'id',
        'name',
        'status',
        'category',
        'language',
        'components',
        'correct_category',
        'cta_url_link_tracking_opted_out',
        'degrees_of_freedom_spec',
        'library_template_name',
        'message_send_ttl_seconds',
        'parameter_format',
        'previous_category',
        'quality_score',
        'rejected_reason',
        'sub_category',
    ], ?int $limit = null): ?array
    {
        if (! config('services.whatsapp.enabled')) {
            Log::warning('WhatsApp templates - API disabled.');

            return null;
        }

        $endpoint = config('services.whatsapp.endpoint');
        $wabaId = config('services.whatsapp.business_id');
        $accessToken = config('services.whatsapp.access_token');

        if (! $endpoint || ! $wabaId || ! $accessToken) {
            Log::error('WhatsApp templates - configuration missing.');

            return null;
        }

        $query = [
            'fields' => implode(',', $fields),
        ];

        if (! is_null($limit)) {
            $query['limit'] = $limit;
        }

        $url = rtrim($endpoint, '/').'/'.$wabaId.'/message_templates';

        try {
            $response = Http::withToken($accessToken)
                ->get($url, $query);

            $data = $response->json();

            if ($response->successful()) {
                Log::debug('WhatsApp templates retrieved', ['query' => $query]);

                return $data;
            }

            // If Graph API returned code 100 (tried accessing nonexisting field),
            // retry with reduced fields and without filters as a fallback.
            $errorCode = $data['error']['code'] ?? null;
            if ($errorCode === 100) {
                Log::warning('WhatsApp templates query failed with code 100, retrying with reduced fields', [
                    'original_query' => $query,
                    'json' => $data,
                ]);

                // Retry with minimal fields (id,name) and no filters
                $retryQuery = ['fields' => implode(',', ['id', 'name'])];
                if (! is_null($limit)) {
                    $retryQuery['limit'] = $limit;
                }

                try {
                    $retryResponse = Http::withToken($accessToken)->get($url, $retryQuery);
                    $retryData = $retryResponse->json();

                    if ($retryResponse->successful()) {
                        Log::debug('WhatsApp templates retrieved on retry', ['retry_query' => $retryQuery]);

                        return $retryData;
                    }

                    Log::error('WhatsApp templates retry failed', [
                        'status' => $retryResponse->status(),
                        'body' => $retryResponse->body(),
                        'json' => $retryData,
                    ]);

                    // return retry error payload
                    return $retryData;
                } catch (\Exception $e) {
                    Log::error('WhatsApp templates retry exception: '.$e->getMessage(), ['exception' => $e]);

                    return null;
                }
            }

            // Log the parsed JSON error when possible for easier debugging
            Log::error('WhatsApp templates list error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $data,
            ]);

            // Return the API error payload so callers can inspect the error message
            return $data;
        } catch (\Exception $e) {
            Log::error('WhatsApp templates list exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    /**
     * Retrieve the message_template_namespace from the WABA
     * GET /<WABA_ID>?fields=message_template_namespace
     */
    protected function getTemplateNamespace(): ?string
    {
        $config = $this->getWhatsAppConfig();
        if (! $config) {
            return null;
        }

        $url = rtrim($config['endpoint'], '/').'/'.$config['waba_id'];

        try {
            $response = Http::withToken($config['access_token'])
                ->get($url, ['fields' => 'message_template_namespace']);

            if ($response->successful()) {
                $data = $response->json();

                return $data['message_template_namespace'] ?? null;
            }

            Log::error('WhatsApp template namespace error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp template namespace exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }
}
