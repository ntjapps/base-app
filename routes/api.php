<?php

use App\Http\Controllers\AppConstController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServerManController;
use App\Http\Controllers\UserManController;
use Illuminate\Support\Facades\Route;

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
Route::middleware(['xss'])->group(function () {
    /** Get Constant */
    Route::post('/post-app-const', [AppConstController::class, 'mainConst'])->name('app-const');
    Route::post('/post-log-agent', [AppConstController::class, 'logAgent'])->name('log-agent');
    Route::post('/post-get-current-app-version', [AppConstController::class, 'getCurrentAppVersion'])->name('post-get-current-app-version');

    /** Login Routes need rate limit to prevent attacks */
    Route::post('/post-token', [AuthController::class, 'postToken'])->name('post-token')->middleware(['throttle:api-secure']);

    /** Routes that need authentication first */
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/post-token-revoke', [AuthController::class, 'postTokenRevoke'])->name('post-token-revoke')->middleware(['throttle:api-secure']);
        Route::post('/post-update-profile', [ProfileController::class, 'updateProfile'])->name('post-update-profile');

        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            Route::post('/get-user-list', [UserManController::class, 'getUserList'])->name('get-user-list');
            Route::post('/get-user-role-perm', [UserManController::class, 'getUserRolePerm'])->name('get-user-role-perm');
            Route::post('/post-user-man-submit', [UserManController::class, 'postUserManSubmit'])->name('post-user-man-submit');
            Route::post('/post-delete-user-man-submit', [UserManController::class, 'postDeleteUserManSubmit'])->name('post-delete-user-man-submit');
            Route::post('/post-reset-password-user-man-submit', [UserManController::class, 'postResetPasswordUserManSubmit'])->name('post-reset-password-user-man-submit');

            Route::post('/get-server-logs', [ServerManController::class, 'getServerLogs'])->name('get-server-logs');
            Route::post('/post-clear-app-cache', [ServerManController::class, 'postClearAppCache'])->name('post-clear-app-cache');
        });
    });
});
