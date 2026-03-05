<?php

use App\Http\Controllers\AiModelInstructionManController;
use App\Http\Controllers\AppConstController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CeleryQueueController;
use App\Http\Controllers\DivisionManController;
use App\Http\Controllers\PassportManController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleManController;
use App\Http\Controllers\ServerManController;
use App\Http\Controllers\TagManController;
use App\Http\Controllers\UserManController;
use App\Http\Controllers\WaApiController;
use App\Http\Controllers\WhatsappManController;
use App\Http\Controllers\WhatsappTemplateManController;
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

        /** Task Status API */
        Route::prefix('tasks')->group(function () {
            Route::get('/', [\App\Http\Controllers\TaskStatusController::class, 'getTaskList'])->name('get-task-list');
            Route::get('/{taskId}', [\App\Http\Controllers\TaskStatusController::class, 'getTaskStatus'])->name('get-task-status');
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
                Route::get('/route-analytics', [ServerManController::class, 'getRouteAnalytics'])->name('get-route-analytics');
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

            /** Division Management API */
            Route::prefix('division-man')->group(function () {
                Route::get('/divisions', [DivisionManController::class, 'getDivisionList'])->name('get-division-list');
                Route::post('/divisions', [DivisionManController::class, 'postDivisionManSubmit'])->name('post-division-man-submit');
                Route::delete('/divisions/{division}', [DivisionManController::class, 'postDeleteDivisionManSubmit'])->name('post-delete-division-man-submit');
            });

            /** Tag Management API */
            Route::prefix('tag-man')->group(function () {
                Route::get('/tags', [TagManController::class, 'getTagList'])->name('get-tag-list');
                Route::post('/tags', [TagManController::class, 'postTagManSubmit'])->name('post-tag-man-submit');
                Route::delete('/tags/{tag}', [TagManController::class, 'postDeleteTagManSubmit'])->name('post-delete-tag-man-submit');
            });

            /** AI Model Instruction Management API */
            Route::prefix('ai-model-instruction-man')->group(function () {
                Route::get('/instructions', [AiModelInstructionManController::class, 'getAiModelInstructionList'])->name('get-ai-model-instruction-list');
                Route::post('/instructions', [AiModelInstructionManController::class, 'postAiModelInstructionManSubmit'])->name('post-ai-model-instruction-man-submit');
                Route::post('/import-from-file', [AiModelInstructionManController::class, 'postImportAiModelInstructionFromFile'])->name('post-ai-model-instruction-import-file');
                Route::post('/export-to-file', [AiModelInstructionManController::class, 'postExportAiModelInstructionToFile'])->name('post-ai-model-instruction-export-file');
                Route::delete('/instructions/{aiModelInstruction}', [AiModelInstructionManController::class, 'postDeleteAiModelInstructionManSubmit'])->name('post-delete-ai-model-instruction-man-submit');
            });

            // WhatsApp APIs maintained under a dedicated permission-based middleware for non-super-admin roles
        });

        Route::middleware(['can:hasPermission,whatsapp.view'])->group(function () {
            Route::prefix('whatsapp')->group(function () {
                /** Whatsapp Management */
                Route::get('/stats', [WhatsappManController::class, 'getWhatsappStats'])->name('whatsapp-stats');
                Route::get('/messages', [WhatsappManController::class, 'getWhatsappMessagesList'])->name('whatsapp-messages-list');
                Route::get('/messages/{phone}', [WhatsappManController::class, 'getWhatsappMessagesDetail'])->name('whatsapp-messages-detail');

                /** Conversation Management */
                Route::post('/conversations/claim', [WhatsappManController::class, 'claimConversation'])->name('whatsapp-conversation-claim');
                Route::post('/conversations/resolve', [WhatsappManController::class, 'resolveConversation'])->name('whatsapp-conversation-resolve');
                Route::post('/conversations/tags', [WhatsappManController::class, 'addConversationTags'])->name('whatsapp-conversation-tags-add');
                Route::delete('/conversations/tags', [WhatsappManController::class, 'removeConversationTag'])->name('whatsapp-conversation-tags-remove');

                /** Whatsapp Template Management */
                Route::get('/templates', [WhatsappTemplateManController::class, 'getTemplatesList'])->name('whatsapp-templates-list');
                Route::post('/templates', [WhatsappTemplateManController::class, 'createTemplateAction'])->name('whatsapp-template-create');
                Route::patch('/templates/{templateId}', [WhatsappTemplateManController::class, 'editTemplateAction'])->name('whatsapp-template-update');
                Route::delete('/templates/{templateId}', [WhatsappTemplateManController::class, 'deleteTemplateAction'])->name('whatsapp-template-delete');
            });
        });

        // Post / reply requires an additional permission
        Route::middleware(['can:hasPermission,whatsapp.reply'])->group(function () {
            Route::prefix('whatsapp')->group(function () {
                Route::post('/messages/{phone}/reply', [WhatsappManController::class, 'postReplyWhatsappMessage'])->name('whatsapp-messages-reply');
            });
        });
    });

    Route::middleware([EnsureClientIsResourceOwner::class.':queue'])->prefix('queue')->group(function () {
        Route::get('/test', function () {
            if (! app()->environment('local')) {
                return response()->json(['status' => 'error', 'message' => 'This feature is only available in local environment.'], 403);
            } else {
                return response()->json(['status' => 'success']);
            }
        })->name('queue-test');

        Route::get('/health', function () {
            return response()->json(['status' => 'success', 'message' => 'Queue API is healthy.']);
        })->name('queue-health-check');

        Route::post('/clear-cache', [CeleryQueueController::class, 'clearCache'])->name('queue-clear-cache');
        Route::post('/clear-permissions', [CeleryQueueController::class, 'clearPermissions'])->name('queue-clear-permissions');

        Route::post('/notifications', [CeleryQueueController::class, 'sendNotification'])->name('queue-send-notification');
        Route::post('/logs', [CeleryQueueController::class, 'sendLog'])->name('queue-send-log');
        Route::post('/callbacks', [CeleryQueueController::class, 'sendCallbacks'])->name('queue-send-callbacks');
    });
});
