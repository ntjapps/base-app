<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WaApiMetaAppManagement
{
    /**
     * Register (subscribe) an app to receive webhook callbacks and optionally override the callback URI.
     *
     * @param  string  $phoneNumberId  The phone number id (business phone) that owns the subscribed_apps edge
     * @param  string  $accessToken  A page or system access token with required permissions
     * @param  string|null  $overrideCallbackUri  Optional callback URI to override the webhook endpoint
     * @param  string|null  $verifyToken  Optional verify token used for handshake
     * @return array [success => bool, status => int|null, body => array|null, error => string|null]
     */
    protected function registerMetaAppWebhook(?string $wabaId = null, ?string $accessToken = null, ?string $overrideCallbackUri = null, ?string $verifyToken = null): array
    {
        $base = config('services.whatsapp.endpoint');
        $wabaId = $wabaId ?? config('services.whatsapp.business_id');
        $accessToken = $accessToken ?? config('services.whatsapp.access_token');

        if (! $base) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.endpoint in config');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing endpoint'];
        }

        if (! $wabaId) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.business_id (WABA ID) in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing waba id'];
        }

        if (! $accessToken) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.access_token in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing access token'];
        }

        $url = rtrim($base, '/').'/'.trim($wabaId, '/').'/subscribed_apps';

        $form = [];
        if ($overrideCallbackUri) {
            $form['override_callback_uri'] = $overrideCallbackUri;
        }
        if ($verifyToken) {
            $form['verify_token'] = $verifyToken;
        }

        try {
            $resp = Http::withToken($accessToken)
                ->asForm()
                ->post($url, $form);

            if ($resp->successful()) {
                return ['success' => true, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => null];
            }

            Log::error('WaApiMetaAppManagement register failed', ['status' => $resp->status(), 'body' => $resp->body()]);

            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => 'http_error'];
        } catch (\Exception $e) {
            Log::error('WaApiMetaAppManagement exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * List subscribed apps (webhook subscriptions) for a phone number id
     *
     * @param  string  $phoneNumberId
     * @return array [success => bool, status => int|null, body => array|null, error => string|null]
     */
    protected function listMetaAppSubscriptions(?string $wabaId = null, ?string $accessToken = null): array
    {
        $base = config('services.whatsapp.endpoint');
        $wabaId = $wabaId ?? config('services.whatsapp.business_id');
        $accessToken = $accessToken ?? config('services.whatsapp.access_token');
        if (! $base) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.endpoint in config');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing endpoint'];
        }

        if (! $wabaId) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.business_id (WABA ID) in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing waba id'];
        }

        if (! $accessToken) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.access_token in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing access token'];
        }

        $url = rtrim($base, '/').'/'.trim($wabaId, '/').'/subscribed_apps';

        try {
            $resp = Http::withToken($accessToken)->get($url);

            if ($resp->successful()) {
                return ['success' => true, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => null];
            }

            Log::error('WaApiMetaAppManagement list failed', ['status' => $resp->status(), 'body' => $resp->body()]);

            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => 'http_error'];
        } catch (\Exception $e) {
            Log::error('WaApiMetaAppManagement exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete (unsubscribe) all apps/webhook subscriptions for a phone number id
     * (Graph API DELETE to /{phone_number_id}/subscribed_apps)
     *
     * @param  string  $phoneNumberId
     * @return array [success => bool, status => int|null, body => array|null, error => string|null]
     */
    protected function deleteMetaAppSubscription(?string $wabaId = null, ?string $accessToken = null): array
    {
        $base = config('services.whatsapp.endpoint');
        $wabaId = $wabaId ?? config('services.whatsapp.business_id');
        $accessToken = $accessToken ?? config('services.whatsapp.access_token');
        if (! $base) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.endpoint in config');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing endpoint'];
        }

        if (! $wabaId) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.business_id (WABA ID) in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing waba id'];
        }

        if (! $accessToken) {
            Log::error('WaApiMetaAppManagement: missing whatsapp.access_token in config or arguments');

            return ['success' => false, 'status' => null, 'body' => null, 'error' => 'missing access token'];
        }

        $url = rtrim($base, '/').'/'.trim($wabaId, '/').'/subscribed_apps';

        try {
            $resp = Http::withToken($accessToken)->delete($url);

            if ($resp->successful()) {
                return ['success' => true, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => null];
            }

            Log::error('WaApiMetaAppManagement delete failed', ['status' => $resp->status(), 'body' => $resp->body()]);

            return ['success' => false, 'status' => $resp->status(), 'body' => $resp->json(), 'error' => 'http_error'];
        } catch (\Exception $e) {
            Log::error('WaApiMetaAppManagement exception', ['message' => $e->getMessage()]);

            return ['success' => false, 'status' => null, 'body' => null, 'error' => $e->getMessage()];
        }
    }
}
