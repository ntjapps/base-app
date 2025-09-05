<?php

namespace App\Http\Controllers;

use App\Interfaces\CentralCacheInterfaceClass;
use App\Interfaces\InterfaceClass;
use App\Rules\TokenPlatformValidation;
use App\Traits\LogContext;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\OAuth2\Server\Exception\OAuthServerException;

class AppConstController extends Controller
{
    use LogContext;

    /**
     * POST app constants
     */
    public function mainConst(Request $request): HttpJsonResponse
    {

        try {
            $authCheck = Auth::check() ? true : Auth::guard('api')->check();
            $user = Auth::user() ?? Auth::guard('api')->user();
            Log::debug('User is requesting app constants', $this->getLogContext($request, $user));
        } catch (OAuthServerException $e) {
            Log::warning('Client is requesting app constants but not authenticated', $this->getLogContext($request, $user));

            return response()->json([
                'isAuth' => false,
            ], 200);
        }

        /** Menu Items */
        if ($authCheck) {
            $menuItems = CentralCacheInterfaceClass::mainMenuCache($user);
        }

        /** Constant now set in Vue State, this now used to check if authenticated or not */
        /** @disregard P1013 Auth facade used to fetch model */
        return response()->json([
            /** App Name */
            'appName' => config('app.name'),
            'appVersion' => InterfaceClass::readApplicationVersion(),
            'userName' => $user?->name ?? '',
            'userId' => $user?->id ?? '',

            /** Check if Auth */
            'isAuth' => $authCheck,

            /** Menu Items */
            'menuItems' => $menuItems ?? [],
        ], 200);
    }

    /**
     * POST log when browser unsupported
     */
    public function logAgent(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        /** Log unsupported browser trigger from client */
        Log::debug('Unsupported browser trigger', $this->getLogContext($request, $user, ['userAgent' => $request->userAgent()]));

        return response()->json('OK', 200);
    }

    /**
     * POST app version updater
     */
    public function getCurrentAppVersion(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('API hit trigger get current app version', $this->getLogContext($request, $user));

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'app_version' => ['required', 'string'],
            'device_id' => ['required', 'string'],
            'device_platform' => ['required', new TokenPlatformValidation],
        ]);
        if ($validate->fails()) {
            Log::warning('API hit trigger validation failed', $this->getLogContext($request, $user, ['errors' => $validate->errors()]));

            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('API hit trigger validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        /** Get Current App Version */
        (string) $currentAppVersion = config('mobile.app_version');
        (bool) $forceUpdate = config('mobile.app_force_update');

        /** If force update then submit force update */
        if ($forceUpdate) {
            Log::info('API hit trigger force update', $this->getLogContext($request, $user));

            return response()->json([
                'appUpdate' => true,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        }

        /** Check Current Semversion from Application */
        if (substr($validated['app_version'], 0, 1) === 'v') {
            (bool) $checkSemVersion = version_compare($validated['app_version'], $currentAppVersion, '>=');
        } else {
            (bool) $checkSemVersion = $validated['app_version'] === $currentAppVersion;
        }

        /** If current version is same with device version then submit no update */
        if ($checkSemVersion) {
            Log::info('API hit trigger no update', $this->getLogContext($request, $user));

            return response()->json([
                'appUpdate' => false,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        } else {
            Log::info('API hit trigger update', $this->getLogContext($request, $user));

            return response()->json([
                'appUpdate' => true,
                'appVersion' => $currentAppVersion,
                'deviceVersion' => $validated['app_version'],
            ]);
        }
    }

    /**
     * POST get notification list
     */
    public function getNotificationList(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('API hit trigger get notification list', $this->getLogContext($request, $user));

        /** Get Notification List */
        $notificationList = $user?->notifications;

        /** Return Response */
        return response()->json($notificationList);
    }

    /**
     * POST post notification as read
     */
    public function postNotificationAsRead(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('API hit trigger post notification as read', $this->getLogContext($request, $user));

        /** Validate Input */
        $validate = Validator::make($request->all(), [
            'notification_id' => ['nullable', 'exists:notifications,id'],
        ]);
        if ($validate->fails()) {
            Log::warning('API hit trigger post notification as read failed', $this->getLogContext($request, $user, ['errors' => $validate->errors()]));

            throw new ValidationException($validate);
        }
        (array) $validated = $validate->validated();

        $validatedLog = $validated;
        Log::info('API hit trigger post notification as read validation', $this->getLogContext($request, $user, ['validated' => json_encode($validatedLog)]));

        /** Updated Notification as Read */
        DB::beginTransaction();
        try {
            if (! is_null($validated['notification_id'])) {
                /** @disregard */
                $user?->notifications->where('id', $validated['notification_id'])->markAsRead();
            } else {
                $user?->unreadNotifications->markAsRead();
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('API hit trigger post notification as read failed', $this->getLogContext($request, $user, ['errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]));

            throw $e;
        }

        /** Return Response */
        return response()->json('OK', 200);
    }

    /**
     * POST post notification clear all
     */
    public function postNotificationClearAll(Request $request): HttpJsonResponse
    {
        $user = Auth::user() ?? Auth::guard('api')->user();
        Log::debug('API hit trigger post notification clear all', $this->getLogContext($request, $user));

        /** Clear All Notification */
        DB::beginTransaction();
        try {
            /** @disregard */
            $user?->notifications()->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('API hit trigger post notification clear all failed', $this->getLogContext($request, $user, ['errors' => $e->getMessage(), 'previous' => $e->getPrevious()?->getMessage()]));

            throw $e;
        }

        /** Return Response */
        return response()->json('OK', 200);
    }
}
