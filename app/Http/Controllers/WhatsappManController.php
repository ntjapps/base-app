<?php

namespace App\Http\Controllers;

use App\Exceptions\CommonCustomException;
use App\Interfaces\MenuItemClass;
use App\Models\WaApiMeta\WaApiMessageThreads;
use App\Models\WaApiMeta\WaMessageWebhookLog;
use App\Traits\JsonResponse;
use App\Traits\LogContext;
use App\Traits\WaApiMeta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        return view('base-components.base', [
            'pageTitle' => 'WhatsApp Management',
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

        $table = (new WaApiMessageThreads)->getTable();

        // Subquery: latest message timestamp per phone number
        $latestPerPhone = WaApiMessageThreads::select('phone_number', DB::raw('MAX(last_message_at) as last_message_at'))
            ->groupBy('phone_number');

        // Join back to get the corresponding rows for those latest timestamps
        $threads = WaApiMessageThreads::joinSub($latestPerPhone, 'latest', function ($join) use ($table) {
            $join->on($table.'.phone_number', '=', 'latest.phone_number')
                ->on($table.'.last_message_at', '=', 'latest.last_message_at');
        })
            ->select([
                $table.'.id',
                $table.'.phone_number',
                $table.'.last_message_at',
                $table.'.messageable_id',
                $table.'.messageable_type',
            ])
            ->with('messageable')
            ->orderBy($table.'.last_message_at', 'desc')
            ->get()
            // Safety: if two rows share identical timestamp per phone, keep the first (latest)
            ->unique('phone_number')
            ->values();

        $data = $threads->map(function (WaApiMessageThreads $thread) {
            $preview = $this->extractMessagePreview($thread->messageable);

            return [
                'id' => $thread->id,
                'phone_number' => $thread->phone_number,
                'last_message_at' => $thread->last_message_at?->toDateTimeString(),
                'message_preview' => $preview,
            ];
        });

        return response()->json($data);
    }

    /**
     * Extract a short preview from messageable model
     */
    private function extractMessagePreview($messageable): ?string
    {
        if (! $messageable) {
            return null;
        }

        $candidates = ['message', 'text', 'body', 'content', 'caption', 'preview', 'message_content'];
        foreach ($candidates as $field) {
            $val = $messageable->getAttribute($field);
            if (is_string($val) && $val !== '') {
                return Str::limit(trim($val), 140);
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
     * POST request to get WhatsApp message thread details
     */
    public function getWhatsappMessagesDetail(Request $request, string $phone): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('User is requesting WhatsApp message thread details', $this->getLogContext($request, $user));

        /** Fetch Thread Details */
        $threadDetail = WaApiMessageThreads::with(['messageable'])
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
            $currentTime = Carbon::now()->subMinutes(5); // Reduce service window by 5 minutes for safety
            $latestTimeToReplyWindow = $messageTime->addDays(1);

            if ($currentTime->greaterThan($latestTimeToReplyWindow)) {
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

        try {
            $this->sendWhatsAppMessage($phone, $validated['message']);

            /** Add number to exception from AI Reply */
            $this->addToAIExceptionReply($phone);

            Log::notice('WhatsApp message sent successfully', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50)]));
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp message', $this->getLogContext($request, $user, ['phone_number' => $phone, 'message' => Str::limit($validated['message'], 50), 'error' => $e->getMessage()]));

            throw new CommonCustomException('Failed to send WhatsApp message.', 422, $e);
        }

        return response()->json(['status' => 'success', 'message' => 'Reply sent successfully.']);
    }
}
