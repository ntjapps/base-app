<?php

use App\Http\Controllers\AppConstController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CeleryQueueController;
use App\Http\Controllers\PassportManController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleManController;
use App\Http\Controllers\ServerManController;
use App\Http\Controllers\UserManController;
use App\Http\Controllers\WaApiController;
use App\Http\Controllers\WhatsappManController;
use App\Http\Middleware\XssProtection;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\EnsureClientIsResourceOwner;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/** All API Route should be sanitized with XSS Middleware */
Route::prefix('v1')->middleware([XssProtection::class])->group(function () {
    /** Get Constant */
    Route::prefix('const')->group(function () {
        Route::get('/app', [AppConstController::class, 'mainConst'])->name('app-const');
        Route::post('/logs/agent', [AppConstController::class, 'logAgent'])->name('log-agent');
        Route::get('/app/version', [AppConstController::class, 'getCurrentAppVersion'])->name('post-get-current-app-version');
    });

    /** WA Api Webhook */
    Route::prefix('whatsapp')->group(function () {
        Route::get('/webhook/{veriId}', [WaApiController::class, 'whatsappWebhookGet'])->name('whatsapp-webhook-get');
        Route::post('/webhook/{veriId}', [WaApiController::class, 'whatsappWebhookPost'])->name('whatsapp-webhook-post');
    });

    /** Login Routes need rate limit to prevent attacks */
    Route::prefix('auth')->middleware(['throttle:10,1'])->group(function () {
        Route::post('/token', [AuthController::class, 'postToken'])->name('post-token');
        Route::delete('/token', [AuthController::class, 'postTokenRevoke'])->name('post-token-revoke')->middleware((['auth:api']));
    });

    /** Routes that need authentication first */
    Route::middleware(['auth:api'])->group(function () {
        Route::prefix('profile')->group(function () {
            Route::get('/notifications', [AppConstController::class, 'getNotificationList'])->name('get-notification-list');
            Route::patch('/notifications/read', [AppConstController::class, 'postNotificationAsRead'])->name('post-notification-as-read');
            Route::delete('/notifications', [AppConstController::class, 'postNotificationClearAll'])->name('post-notification-clear-all');
            Route::patch('/', [ProfileController::class, 'updateProfile'])->name('post-update-profile');
        });

        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            /** User Management API */
            Route::prefix('user-man')->group(function () {
                Route::get('/users', [UserManController::class, 'getUserList'])->name('get-user-list');
                Route::get('/users/role-perm', [UserManController::class, 'getUserRolePerm'])->name('get-user-role-perm');
                Route::post('/users', [UserManController::class, 'postUserManSubmit'])->name('post-user-man-submit');
                Route::delete('/users/{user}', [UserManController::class, 'postDeleteUserManSubmit'])->name('post-delete-user-man-submit');
                Route::patch('/users/{user}/password', [UserManController::class, 'postResetPasswordUserManSubmit'])->name('post-reset-password-user-man-submit');
            });

            /** Role Management API */
            Route::prefix('role-man')->group(function () {
                Route::get('/roles', [RoleManController::class, 'getRoleList'])->name('get-role-list');
                Route::post('/roles', [RoleManController::class, 'postRoleSubmit'])->name('post-role-submit');
                Route::delete('/roles/{role}', [RoleManController::class, 'postDeleteRoleSubmit'])->name('post-delete-role-submit');
            });

            Route::prefix('server-man')->group(function () {
                Route::get('/logs', [ServerManController::class, 'getServerLogs'])->name('get-server-logs');
                Route::delete('/cache', [ServerManController::class, 'postClearAppCache'])->name('post-clear-app-cache');
            });

            Route::prefix('oauth')->group(function () {
                /** Passport Client Management */
                Route::get('/clients', [PassportManController::class, 'listPassportClients'])->name('passport.clients.index');
                Route::patch('/clients/{client}/secret', [PassportManController::class, 'resetClientSecret'])->name('passport.clients.reset-secret');
                Route::delete('/clients/{client}', [PassportManController::class, 'deletePassportClient'])->name('passport.clients.destroy');
                Route::patch('/clients/{client}', [PassportManController::class, 'updatePassportClient'])->name('passport.clients.update');
                Route::post('/clients', [PassportManController::class, 'createPassportClient'])->name('passport.clients.store');
            });

            Route::prefix('whatsapp')->group(function () {
                /** Whatsapp Management */
                Route::get('/messages', [WhatsappManController::class, 'getWhatsappMessagesList'])->name('whatsapp-messages-list');
                Route::get('/messages/{phone}', [WhatsappManController::class, 'getWhatsappMessagesDetail'])->name('whatsapp-messages-detail');
                Route::post('/messages/{phone}/reply', [WhatsappManController::class, 'postReplyWhatsappMessage'])->name('whatsapp-messages-reply');
            });
        });
    });

    Route::middleware([EnsureClientIsResourceOwner::class.':rabbitmq'])->prefix('rabbitmq')->group(function () {
        Route::get('/test', function () {
            if (! app()->environment('local')) {
                return response()->json(['status' => 'error', 'message' => 'This feature is only available in local environment.'], 403);
            } else {
                return response()->json(['status' => 'success']);
            }
        })->name('rabbitmq-test-rabbitmq');

        Route::post('/notifications', [CeleryQueueController::class, 'sendNotification'])->name('rabbitmq-send-notification');
        Route::post('/logs', [CeleryQueueController::class, 'sendLog'])->name('rabbitmq-send-log');
        Route::post('/callbacks', [CeleryQueueController::class, 'sendCallbacks'])->name('rabbitmq-send-callbacks');
    });
});
