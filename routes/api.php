<?php

use App\Http\Controllers\AppConstController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CeleryQueueController;
use App\Http\Controllers\PassportManController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleManController;
use App\Http\Controllers\ServerManController;
use App\Http\Controllers\UserManController;
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
        Route::post('/post-app-const', [AppConstController::class, 'mainConst'])->name('app-const');
        Route::post('/post-log-agent', [AppConstController::class, 'logAgent'])->name('log-agent');
        Route::post('/post-get-current-app-version', [AppConstController::class, 'getCurrentAppVersion'])->name('post-get-current-app-version');
    });

    /** Login Routes need rate limit to prevent attacks */
    Route::prefix('auth')->middleware(['throttle:10,1'])->group(function () {
        Route::post('/post-token', [AuthController::class, 'postToken'])->name('post-token');
        Route::post('/post-token-revoke', [AuthController::class, 'postTokenRevoke'])->name('post-token-revoke')->middleware((['auth:api']));
    });

    /** Routes that need authentication first */
    Route::middleware(['auth:api'])->group(function () {
        Route::prefix('profile')->group(function () {
            Route::post('/get-notification-list', [AppConstController::class, 'getNotificationList'])->name('get-notification-list');
            Route::post('/post-notification-as-read', [AppConstController::class, 'postNotificationAsRead'])->name('post-notification-as-read');
            Route::post('/post-notification-clear-all', [AppConstController::class, 'postNotificationClearAll'])->name('post-notification-clear-all');
            Route::post('/post-update-profile', [ProfileController::class, 'updateProfile'])->name('post-update-profile');
        });

        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            /** User Management API */
            Route::prefix('user-man')->group(function () {
                Route::post('/get-user-list', [UserManController::class, 'getUserList'])->name('get-user-list');
                Route::post('/get-user-role-perm', [UserManController::class, 'getUserRolePerm'])->name('get-user-role-perm');
                Route::post('/post-user-man-submit', [UserManController::class, 'postUserManSubmit'])->name('post-user-man-submit');
                Route::post('/post-delete-user-man-submit', [UserManController::class, 'postDeleteUserManSubmit'])->name('post-delete-user-man-submit');
                Route::post('/post-reset-password-user-man-submit', [UserManController::class, 'postResetPasswordUserManSubmit'])->name('post-reset-password-user-man-submit');
            });

            /** Role Management API */
            Route::prefix('role-man')->group(function () {
                Route::post('/get-role-list', [RoleManController::class, 'getRoleList'])->name('get-role-list');
                Route::post('/post-role-submit', [RoleManController::class, 'postRoleSubmit'])->name('post-role-submit');
                Route::post('/post-delete-role-submit', [RoleManController::class, 'postDeleteRoleSubmit'])->name('post-delete-role-submit');
            });

            Route::prefix('server-man')->group(function () {
                Route::post('/get-server-logs', [ServerManController::class, 'getServerLogs'])->name('get-server-logs');
                Route::post('/post-clear-app-cache', [ServerManController::class, 'postClearAppCache'])->name('post-clear-app-cache');
            });

            Route::prefix('oauth')->group(function () {
                /** Passport Client Management */
                Route::post('/post-get-oauth-client', [PassportManController::class, 'listPassportClients'])->name('passport.clients.index');
                Route::post('/post-reset-oauth-secret', [PassportManController::class, 'resetClientSecret'])->name('passport.clients.reset-secret');
                Route::post('/post-delete-oauth-client', [PassportManController::class, 'deletePassportClient'])->name('passport.clients.destroy');
                Route::post('/post-update-oauth-client', [PassportManController::class, 'updatePassportClient'])->name('passport.clients.update');
                Route::post('/post-create-oauth-client', [PassportManController::class, 'createPassportClient'])->name('passport.clients.store');
            });
        });
    });

    Route::middleware([EnsureClientIsResourceOwner::class.':rabbitmq'])->prefix('rabbitmq')->group(function () {
        Route::post('/test-rabbitmq', function () {
            if (! app()->environment('local')) {
                return response()->json(['status' => 'error', 'message' => 'This feature is only available in local environment.'], 403);
            } else {
                return response()->json(['status' => 'success']);
            }
        })->name('rabbitmq-test-rabbitmq');

        Route::post('/send-notification', [CeleryQueueController::class, 'sendNotification'])->name('rabbitmq-send-notification');
        Route::post('/send-log', [CeleryQueueController::class, 'sendLog'])->name('rabbitmq-send-log');
        Route::post('/send-callbacks', [CeleryQueueController::class, 'sendCallbacks'])->name('rabbitmq-send-callbacks');
    });
});
