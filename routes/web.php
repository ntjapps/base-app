<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashController;
use App\Http\Controllers\PassportManController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleManController;
use App\Http\Controllers\ServerManController;
use App\Http\Controllers\UserManController;
use App\Http\Middleware\ProfileFillIfEmpty;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

/** Route start here, WEB used for GET only */
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['status' => 'success', 'csrf_token' => app()->environment('production') ? 'token' : csrf_token()]);
});

Route::get('/php-ip-detect', function () {
    if (! app()->environment('local')) {
        return response()->json(['status' => 'error', 'message' => 'This feature is only available in local environment.'], 403);
    } else {
        return response()->json(['status' => 'success', 'ip' => request()->ip(), request()->headers->all()]);
    }
});

/** Route for login redirect */
Route::get('/login-redirect', function () {
    return redirect(route('login-page'));
})->name('login');

Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return redirect(route('login-page'));
    })->name('landing-page');

    Route::get('/login', [AuthController::class, 'loginPage'])->name('login-page');
    Route::post('/post-login', [AuthController::class, 'postLogin'])->name('post-login')->middleware(['throttle:10,1']);
});

/** Routes that need authentication first */
Route::middleware(['auth'])->group(function () {
    Route::prefix('auth')->middleware(['throttle:10,1'])->group(function () {
        Route::post('/post-logout', [AuthController::class, 'postLogout'])->name('post-logout');
        Route::get('/get-logout', [AuthController::class, 'getLogout'])->name('get-logout');
    });

    Route::get('/profile', [ProfileController::class, 'profilePage'])->name('profile');
    /** Check if profile fillled if not, force go to profile page */
    Route::middleware([ProfileFillIfEmpty::class])->group(function () {
        Route::get('/dashboard', [DashController::class, 'dashboardPage'])->name('dashboard');

        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            /** User Management Menu */
            Route::get('/user-man', [UserManController::class, 'userManPage'])->name('user-man');

            /** Role management Menu */
            Route::get('/role-man', [RoleManController::class, 'roleManPage'])->name('role-man');

            /** Server Management Menu */
            Route::get('/server-logs', [ServerManController::class, 'serverLogs'])->name('server-logs');

            /** Passport Management Menu */
            Route::get('/passport-man', [PassportManController::class, 'passportManPage'])->name('passport-man');
        });
    });
});
