<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Interfaces\PermissionConstants;
use App\Jobs\WhatsApp\CreateTemplateJob;
use App\Jobs\WhatsApp\DeleteTemplateJob;
use App\Jobs\WhatsApp\UpdateTemplateJob;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use App\Traits\WaApiMetaTemplate;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WhatsappTemplateManController extends Controller
{
    use JsonResponse, LogContext, WaApiMetaTemplate;

    /**
     * GET whatsapp templates management page
     */
    public function templateManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessed WhatsApp templates management page', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        return view('base-components.base', [
            'pageTitle' => 'Templates',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()?->getName()),
        ]);
    }

    /**
     * GET list of message templates
     */
    public function getTemplatesList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User requesting WhatsApp templates list', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $fields = $request->query('fields', 'id,name,status,category,language,components,correct_category,cta_url_link_tracking_opted_out,degrees_of_freedom_spec,library_template_name,message_send_ttl_seconds,parameter_format,previous_category,quality_score,rejected_reason,sub_category');
        $limit = $request->query('limit');

        $response = $this->getTemplates(explode(',', (string) $fields), $limit ? (int) $limit : null);

        // Extract the data array from Meta API response for consistency with other endpoints
        // Meta API returns { data: [...], paging: {...} }
        if (is_array($response) && isset($response['data']) && is_array($response['data'])) {
            return response()->json([
                'data' => $response['data'],
            ]);
        }

        // If response is null, error, or unexpected format, return empty array
        return response()->json([
            'data' => [],
        ]);
    }

    /**
     * POST create a new template (enqueues task to Go worker)
     */
    public function createTemplateAction(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User creating WhatsApp template', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:512'],
            'language' => ['required', 'string'],
            'category' => ['required', 'string', 'in:AUTHENTICATION,MARKETING,UTILITY'],
            'components' => ['required', 'array'],
            'message_send_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'cta_url_link_tracking_opted_out' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $payload = $validator->validated();

        // Dispatch job to Go worker via RabbitMQ
        CreateTemplateJob::dispatch($payload, $user?->id);

        return $this->jsonSuccess('Create Template', 'Template creation request submitted. Processing in background.');
    }

    /**
     * POST edit an existing template (enqueues task to Go worker)
     */
    public function editTemplateAction(Request $request, string $templateId): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User editing WhatsApp template', $this->getLogContext($request, $user, ['templateId' => $templateId]));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validator = Validator::make($request->all(), [
            'category' => ['nullable', 'string', 'in:AUTHENTICATION,MARKETING,UTILITY'],
            'components' => ['nullable', 'array'],
            'message_send_ttl_seconds' => ['nullable', 'integer', 'min:1'],
            'cta_url_link_tracking_opted_out' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $payload = $validator->validated();

        // Dispatch job to Go worker via RabbitMQ
        UpdateTemplateJob::dispatch($templateId, $payload, $user?->id);

        return $this->jsonSuccess('Edit Template', 'Template update request submitted. Processing in background.');
    }

    /**
     * DELETE a template by templateId (hsm id) from route (enqueues task to Go worker)
     */
    public function deleteTemplateAction(Request $request, string $templateId): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User deleting WhatsApp template', $this->getLogContext($request, $user, ['templateId' => $templateId]));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        // The Meta API may not support filtering by `id` or returning a single detail.
        // Request the templates list and perform client-side lookup for the template id.
        $detail = $this->getTemplates(['name', 'id']);

        // If the API returned an error payload (e.g. ['error' => ...]), surface it
        if (is_array($detail) && array_key_exists('error', $detail)) {
            $err = $detail['error'];
            $msg = $err['message'] ?? 'WhatsApp API error';
            throw ValidationException::withMessages(['template_id' => ["WhatsApp API error: {$msg}"]]);
        }

        $items = [];
        if (is_array($detail) && array_key_exists('data', $detail) && is_array($detail['data'])) {
            $items = $detail['data'];
        } elseif (is_array($detail) && count($detail) && array_key_exists(0, $detail)) {
            // fallback: some callers return array directly
            $items = $detail;
        }

        $templateItem = null;
        foreach ($items as $it) {
            if (isset($it['id']) && (string) $it['id'] === (string) $templateId) {
                $templateItem = $it;
                break;
            }
        }

        if (empty($templateItem)) {
            throw ValidationException::withMessages(['template_id' => ['Template not found']]);
        }

        $name = $templateItem['name'] ?? null;

        if (empty($name)) {
            throw ValidationException::withMessages(['template_id' => ['Template name unavailable for deletion']]);
        }

        // Dispatch job to Go worker via RabbitMQ
        DeleteTemplateJob::dispatch($templateId, $name, $user?->id);

        return $this->jsonSuccess('Delete Template', 'Template deletion request submitted. Processing in background.');
    }
}
