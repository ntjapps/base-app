<?php

namespace App\Http\Controllers;

use App\Interfaces\MenuItemClass;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use App\Traits\WaApiMetaTemplate;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return view('base-components.base', [
            'pageTitle' => 'WhatsApp Templates Management',
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

        $fields = $request->query('fields', 'id,name,status,category,language,components,correct_category,cta_url_link_tracking_opted_out,degrees_of_freedom_spec,library_template_name,message_send_ttl_seconds,parameter_format,previous_category,quality_score,rejected_reason,sub_category');
        $limit = $request->query('limit');

        $data = $this->getTemplates(explode(',', (string) $fields), $limit ? (int) $limit : null);

        return response()->json($data ?? []);
    }

    /**
     * POST create a new template
     */
    public function createTemplateAction(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User creating WhatsApp template', $this->getLogContext($request, $user));

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

        $result = $this->createTemplate($payload['name'], $payload['language'], $payload['category'], $payload['components'], $payload['message_send_ttl_seconds'] ?? null, $payload['cta_url_link_tracking_opted_out'] ?? null);

        if (is_null($result)) {
            return $this->jsonFailed('Create Template', 'Failed to create template');
        }

        return $this->jsonSuccess('Create Template', 'Template created successfully', null, (array) $result);
    }

    /**
     * POST edit an existing template
     */
    public function editTemplateAction(Request $request, string $templateId): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User editing WhatsApp template', $this->getLogContext($request, $user, ['templateId' => $templateId]));

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

        $ok = $this->editTemplate($templateId, $payload);

        if (! $ok) {
            return $this->jsonFailed('Edit Template', 'Failed to edit template');
        }

        return $this->jsonSuccess('Edit Template', 'Template edited successfully');
    }

    /**
     * DELETE a template by templateId (hsm id) from route
     */
    public function deleteTemplateAction(Request $request, string $templateId): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::info('User deleting WhatsApp template', $this->getLogContext($request, $user, ['templateId' => $templateId]));

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

        $ok = $this->deleteTemplateById($templateId, $name);

        if (! $ok) {
            return $this->jsonFailed('Delete Template', 'Failed to delete template');
        }

        return $this->jsonSuccess('Delete Template', 'Template deleted successfully');
    }
}
