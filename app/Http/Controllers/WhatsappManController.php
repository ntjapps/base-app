<?php

namespace App\Http\Controllers;

use App\Exceptions\CommonCustomException;
use App\Interfaces\MenuItemClass;
use App\Interfaces\PermissionConstants;
use App\Jobs\WhatsApp\AddConversationTagsJob;
use App\Jobs\WhatsApp\ClaimConversationJob;
use App\Jobs\WhatsApp\RemoveConversationTagJob;
use App\Jobs\WhatsApp\ResolveConversationJob;
use App\Jobs\WhatsApp\SendMessageJob;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageSentLog;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use App\Traits\WaApiMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WhatsappManController extends Controller
{
    use JsonResponse, LogContext, WaApiMeta;

    /**
     * GET whatsapp management page
     */
    public function whatsappManPage(Request $request): View
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User accessed WhatsApp management page', $this->getLogContext($request, $user));

        // Ensure user is allowed to view WhatsApp pages
        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        return view('base-components.base', [
            'pageTitle' => 'Inbox',
            'expandedKeys' => MenuItemClass::currentRouteExpandedKeys($request->route()->getName()),
        ]);
    }

    /**
     * POST request to get WhatsApp message threads list
     */
    public function getWhatsappMessagesList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting WhatsApp message threads list', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        // Optimized: fetch latest thread ID per phone number using window function
        // to avoid invalid MAX(uuid) on PostgreSQL.
        $latestIds = $this->latestThreadIdsPerPhone();

        $threads = WaApiMessageThreads::whereIn('id', $latestIds)
            ->select([
                'id',
                'phone_number',
                'status',
                'last_message_at',
                'messageable_type',
                'messageable_id',
                'assigned_agent_id',
                'created_at', // often needed for morph
            ])
            ->with(['messageable', 'assignedAgent:id,name'])
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Auto-resolve any older per-phone threads that are not selected as the latest
        try {
            $latestIds = $threads->pluck('id')->toArray();
            $phones = $threads->pluck('phone_number')->toArray();

            // Find any threads for the phones in current result that are not the latest per phone,
            // and mark them as RESOLVED so they don't appear as active conversations.
            WaApiMessageThreads::whereIn('phone_number', $phones)
                ->whereNotIn('id', $latestIds)
                ->whereIn('status', ['OPEN', 'PENDING_HUMAN'])
                ->update(['status' => 'RESOLVED', 'assigned_agent_id' => null]);
        } catch (\Throwable $e) {
            // Log, but don't throw: this should not prevent the request from returning results.
            Log::error('Failed to auto-resolve older WA threads', ['error' => $e->getMessage()]);
        }

        $data = $threads->map(function (WaApiMessageThreads $thread) {
            $preview = $this->extractMessagePreview($thread->messageable);

            // Determine contact name from messageable if available; if last message was a sent log,
            // fall back to the most recent webhook entry for the phone to find the contact name.
            $contactName = null;
            if ($thread->messageable instanceof WaMessageWebhookLog) {
                $contactName = $thread->messageable->contact_name;
            } else {
                // Last message is not a webhook entry (likely a sent log). Try to find most
                // recent webhook for this conversation to obtain the contact_name.
                try {
                    $latestWebhook = WaMessageWebhookLog::where('display_phone_number', $thread->phone_number)
                        ->orWhere('contact_wa_id', $thread->phone_number)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($latestWebhook && $latestWebhook->contact_name) {
                        $contactName = $latestWebhook->contact_name;
                    }
                } catch (\Throwable $e) {
                    // Don't fail the whole response if lookup fails — log and continue.
                    Log::debug('Failed to lookup last webhook contact name', ['error' => $e->getMessage()]);
                }
            }

            // Determine if needs reply (last message is from user and status is not resolved)
            $needsReply = $thread->messageable instanceof WaMessageWebhookLog && $thread->status !== 'RESOLVED';

            return [
                'id' => $thread->id,
                'phone_number' => $thread->phone_number,
                'contact_name' => $contactName,
                'last_message_at' => $thread->last_message_at?->toDateTimeString(),
                'message_preview' => $preview,
                'status' => $thread->status,
                'assigned_agent' => $thread->assignedAgent ? $thread->assignedAgent->name : null,
                'needs_reply' => $needsReply,
            ];
        });

        return response()->json($data);
    }

    /**
     * GET whatsapp stats
     */
    public function getWhatsappStats(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        // Count unique conversations by status from latest thread per phone number.
        $latestIds = $this->latestThreadIdsPerPhone();

        $stats = WaApiMessageThreads::whereIn('id', $latestIds)
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when status = 'OPEN' then 1 end) as open")
            ->selectRaw("count(case when status = 'PENDING_HUMAN' then 1 end) as pending")
            ->selectRaw("count(case when status = 'RESOLVED' then 1 end) as resolved")
            ->first();

        $total = $stats->total;
        $open = $stats->open;
        $pending = $stats->pending;
        $resolved = $stats->resolved;

        return response()->json([
            'total' => $total,
            'open' => $open,
            'pending' => $pending,
            'resolved' => $resolved,
        ]);
    }

    /**
     * Get latest thread IDs per phone number in a UUID-safe, PostgreSQL-safe way.
     *
     * Uses ROW_NUMBER() instead of MAX(id), since id is UUID.
     */
    private function latestThreadIdsPerPhone()
    {
        $ranked = WaApiMessageThreads::query()
            ->select('id')
            ->selectRaw(
                'ROW_NUMBER() OVER (PARTITION BY phone_number ORDER BY COALESCE(last_message_at, created_at) DESC, created_at DESC, id DESC) AS row_num'
            );

        return WaApiMessageThreads::query()
            ->fromSub($ranked, 'ranked_threads')
            ->where('row_num', 1)
            ->pluck('id');
    }

    /**
     * Extract a short preview from messageable model
     */
    private function extractMessagePreview($messageable): ?string
    {
        if (! $messageable) {
            return null;
        }
        // Check well-known string fields first (includes webhook `message_body`).
        $candidates = ['message', 'text', 'body', 'content', 'caption', 'preview', 'message_content', 'message_body'];
        foreach ($candidates as $field) {
            $val = $messageable->getAttribute($field);
            if (is_string($val) && $val !== '') {
                return Str::limit(trim($val), 140);
            }
        }

        // If raw_data is present (webhook), try to extract the first available textual body
        $rawData = $messageable->getAttribute('raw_data');
        if (is_array($rawData) && ! empty($rawData)) {
            $found = $this->findTextInNestedArray($rawData);
            if (! is_null($found) && $found !== '') {
                return Str::limit(trim($found), 140);
            }
        }

        if (method_exists($messageable, '__toString')) {
            $str = (string) $messageable;
            if ($str !== '') {
                return Str::limit($str, 140);
            }
        }

        return null;
    }

    /**
     * Recursively search an array for a 'body' or 'text'->'body' string.
     */
    private function findTextInNestedArray(array $data): ?string
    {
        foreach ($data as $k => $v) {
            if (is_string($k) && in_array($k, ['body']) && is_string($v) && $v !== '') {
                return $v;
            }

            if ($k === 'text' && is_array($v) && isset($v['body']) && is_string($v['body']) && $v['body'] !== '') {
                return $v['body'];
            }

            if (is_array($v)) {
                $res = $this->findTextInNestedArray($v);
                if (! is_null($res) && $res !== '') {
                    return $res;
                }
            }
        }

        return null;
    }

    /**
     * POST request to get WhatsApp message thread details
     */
    public function getWhatsappMessagesDetail(Request $request, string $phone): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting WhatsApp message thread details', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        /** Fetch Thread Details */
        $threadDetail = WaApiMessageThreads::with(['messageable' => function (MorphTo $morphTo) {
            $morphTo->morphWith([
                WaMessageSentLog::class => ['sentByUser'],
            ]);
        }])
            ->where('phone_number', $phone)
            ->get();

        if ($threadDetail->isEmpty()) {
            Log::warning('No message thread found for phone number', $this->getLogContext($request, $user, ['phone_number' => $phone]));

            throw ValidationException::withMessages([
                'phone_number' => ['No message thread found for this phone number.'],
            ]);
        }

        return response()->json($threadDetail);
    }

    /**
     * POST reply to WhatsApp message
     * Replying must be on service window, that is 24 hour since last customer received message
     * For safety application must deny message if not in service window, but service windows in application is reduced by 5 minutes.
     */
    public function postReplyWhatsappMessage(Request $request, string $phone): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is replying to WhatsApp message', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_REPLY);

        // If WhatsApp API is disabled in config, reject with 422 so callers can handle it
        if (! config('services.whatsapp.enabled')) {
            return $this->jsonFailed('WhatsApp API is disabled', 'WhatsApp API is disabled');
        }

        $validate = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:4096'],
        ]);
        if ($validate->fails()) {
            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validateLog = $validated;
        Log::info('Validated request data for reply', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message_length' => strlen($validateLog['message'])]));

        /** Check Service Message Window */
        $latestMessage = WaMessageWebhookLog::where('contact_wa_id', $phone)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestMessage) {
            $messageTime = Carbon::parse($latestMessage->created_at);
            // Reduce service window by 1 minute for safety to prevent Meta API issues
            $latestTimeToReplyWindow = $messageTime->addDays(1)->subMinutes(1);

            if (Carbon::now()->greaterThan($latestTimeToReplyWindow)) {
                Log::warning('User is trying to reply outside service window', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50)]));

                throw ValidationException::withMessages([
                    'phone_number' => ['You can only reply within 24 hours of the last message.'],
                ]);
            }

            Log::debug('User is replying within service window', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50)]));
        } else {
            Log::warning('No previous messages found for this phone number', $this->getLogContext($request, $user, ['phone_number' => $phone]));

            throw ValidationException::withMessages([
                'phone_number' => ['No previous messages found for this phone number.'],
            ]);
        }

        Log::info('Replying to WhatsApp message', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50)]));

        // Dispatch message sending to Go worker via RabbitMQ
        SendMessageJob::dispatch($phone, $validated['message'], false, $user->id);

        /** Add number to exception from AI Reply */
        $this->addToAIExceptionReply($phone);

        Log::notice('WhatsApp message queued for sending', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50)]));

        return response()->json(['status' => 'success', 'message' => 'Reply queued for sending.']);
    }

    /**
     * POST request to claim a conversation (assign to current agent)
     */
    public function claimConversation(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is claiming conversation', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validated = $request->validate([
            'conversation_id' => 'required|uuid|exists:wa_api_message_threads,id',
        ]);

        try {
            // Dispatch to Go worker for processing
            ClaimConversationJob::dispatch($validated['conversation_id'], $user->id);

            Log::info('Conversation claim queued', [
                'conversation_id' => $validated['conversation_id'],
                'agent_id' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Conversation claim request queued.',
                'data' => [
                    'conversation_id' => $validated['conversation_id'],
                    'agent_id' => $user->id,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue conversation claim', [
                'conversation_id' => $validated['conversation_id'],
                'error' => $e->getMessage(),
            ]);

            throw new CommonCustomException('Failed to queue conversation claim.', 422, $e);
        }
    }

    /**
     * POST request to resolve a conversation
     */
    public function resolveConversation(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is resolving conversation', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validated = $request->validate([
            'conversation_id' => 'required|uuid|exists:wa_api_message_threads,id',
        ]);

        try {
            // Dispatch to Go worker for processing
            ResolveConversationJob::dispatch($validated['conversation_id']);

            Log::info('Conversation resolve queued', [
                'conversation_id' => $validated['conversation_id'],
                'resolved_by' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Conversation resolve request queued.',
                'data' => [
                    'conversation_id' => $validated['conversation_id'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue conversation resolve', [
                'conversation_id' => $validated['conversation_id'],
                'error' => $e->getMessage(),
            ]);

            throw new CommonCustomException('Failed to queue conversation resolve.', 422, $e);
        }
    }

    /**
     * POST request to add tags to a conversation
     */
    public function addConversationTags(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is adding tags to conversation', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validated = $request->validate([
            'conversation_id' => 'required|uuid|exists:wa_api_message_threads,id',
            'tags' => 'required|array|min:1',
            'tags.*' => 'required|string|max:100',
        ]);

        try {
            // Dispatch to Go worker for processing
            AddConversationTagsJob::dispatch($validated['conversation_id'], $validated['tags']);

            Log::info('Tags addition queued', [
                'conversation_id' => $validated['conversation_id'],
                'tags' => $validated['tags'],
                'added_by' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tags addition request queued.',
                'data' => [
                    'conversation_id' => $validated['conversation_id'],
                    'tags' => $validated['tags'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue tags addition', [
                'conversation_id' => $validated['conversation_id'],
                'error' => $e->getMessage(),
            ]);

            throw new CommonCustomException('Failed to queue tags addition.', 422, $e);
        }
    }

    /**
     * DELETE request to remove a tag from a conversation
     */
    public function removeConversationTag(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is removing tag from conversation', $this->getLogContext($request, $user));

        Gate::forUser($user)->authorize('hasPermission', PermissionConstants::WHATSAPP_VIEW);

        $validated = $request->validate([
            'conversation_id' => 'required|uuid|exists:wa_api_message_threads,id',
            'tag_name' => 'required|string|max:100',
        ]);

        try {
            // Dispatch to Go worker for processing
            RemoveConversationTagJob::dispatch($validated['conversation_id'], $validated['tag_name']);

            Log::info('Tag removal queued', [
                'conversation_id' => $validated['conversation_id'],
                'tag_name' => $validated['tag_name'],
                'removed_by' => $user->id,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Tag removal request queued.',
                'data' => [
                    'conversation_id' => $validated['conversation_id'],
                    'tag_name' => $validated['tag_name'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue tag removal', [
                'conversation_id' => $validated['conversation_id'],
                'error' => $e->getMessage(),
            ]);

            throw new CommonCustomException('Failed to queue tag removal.', 422, $e);
        }
    }
}
