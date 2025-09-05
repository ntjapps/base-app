<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WaApiMetaTemplate
{
    /**
     * Create a message template for a WhatsApp Business Account (WABA)
     * Uses Business Management API: POST /<WABA_ID>/message_templates
     *
     * @param  string  $language  e.g. en_US
     * @param  string  $category  AUTHENTICATION|MARKETING|UTILITY
     * @param  int|null  $messageSendTtlSeconds  optional TTL in seconds
     * @param  bool|null  $ctaUrlLinkTrackingOptedOut  optional CTA URL link tracking opt-out
     * @return array|null API response or null on error
     */
    protected function createTemplate(string $name, string $language, string $category, array $components = [], ?int $messageSendTtlSeconds = null, ?bool $ctaUrlLinkTrackingOptedOut = null): ?array
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

        $url = rtrim($endpoint, '/')."/{$wabaId}/message_templates";

        $body = [
            'name' => $name,
            'language' => $language,
            'category' => $category,
            'components' => $components,
        ];

        if (! is_null($messageSendTtlSeconds)) {
            $body['message_send_ttl_seconds'] = $messageSendTtlSeconds;
        }

        if (! is_null($ctaUrlLinkTrackingOptedOut)) {
            $body['cta_url_link_tracking_opted_out'] = $ctaUrlLinkTrackingOptedOut;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('WhatsApp template created', ['name' => $name, 'response' => $data]);

                return $data;
            }

            Log::error('WhatsApp template create error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp template create exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
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

        $url = rtrim($endpoint, '/')."/{$wabaId}/message_templates";

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

        $url = rtrim($endpoint, '/')."/{$wabaId}";

        try {
            $response = Http::withToken($accessToken)
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

    /**
     * Edit a template by its template ID (POST /<TEMPLATE_ID>)
     *
     * @param  array  $data  (category/components/message_send_ttl_seconds/cta_url_link_tracking_opted_out)
     * @return bool|null true on success, null on error
     */
    protected function editTemplate(string $templateId, array $data): ?bool
    {
        if (! config('services.whatsapp.enabled')) {
            Log::warning('WhatsApp templates - API disabled.');

            return null;
        }

        $endpoint = config('services.whatsapp.endpoint');
        $accessToken = config('services.whatsapp.access_token');

        if (! $endpoint || ! $accessToken) {
            Log::error('WhatsApp templates - configuration missing.');

            return null;
        }

        $url = rtrim($endpoint, '/')."/{$templateId}";

        try {
            $response = Http::withToken($accessToken)
                ->post($url, $data);

            if ($response->successful()) {
                Log::info('WhatsApp template edited', ['template_id' => $templateId]);

                return true;
            }

            Log::error('WhatsApp template edit error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp template edit exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    /**
     * Delete template(s) by name for a WABA
     * DELETE /<WABA_ID>/message_templates?name=<NAME>
     *
     * @return bool|null true on success, null on error
     */
    protected function deleteTemplateByName(string $name): ?bool
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

        $url = rtrim($endpoint, '/')."/{$wabaId}/message_templates";

        try {
            $response = Http::withToken($accessToken)
                ->delete($url, ['name' => $name]);

            if ($response->successful()) {
                Log::info('WhatsApp template(s) deleted by name', ['name' => $name]);

                return true;
            }

            Log::error('WhatsApp template delete by name error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp template delete by name exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }

    /**
     * Delete a template by id for a WABA
     * DELETE /<WABA_ID>/message_templates?hsm_id=<HSM_ID>&name=<NAME>
     *
     * @param  string  $wabaId
     */
    protected function deleteTemplateById(string $hsmId, string $name): ?bool
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

        $url = rtrim($endpoint, '/')."/{$wabaId}/message_templates";

        try {
            $response = Http::withToken($accessToken)
                ->delete($url, ['hsm_id' => $hsmId, 'name' => $name]);

            if ($response->successful()) {
                Log::info('WhatsApp template deleted by id', ['hsm_id' => $hsmId, 'name' => $name]);

                return true;
            }

            Log::error('WhatsApp template delete by id error', ['status' => $response->status(), 'body' => $response->body()]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp template delete by id exception: '.$e->getMessage(), ['exception' => $e]);

            return null;
        }
    }
}
