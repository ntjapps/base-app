<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashController;
use App\Http\Controllers\ProfileController;
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

/** Route for login redirect */
Route::get('/login-redirect', function () {
    return redirect(route('landing-page'));
})->name('login');

Route::middleware(['guest'])->group(function () {
    Route::get('/', [AuthController::class, 'loginPage'])->name('landing-page');

    Route::post('/post-login', [AuthController::class, 'postLogin'])->name('post-login');
});

/** Routes that need authentication first */
Route::middleware(['auth'])->group(function () {
    Route::post('/post-logout', [AuthController::class, 'postLogout'])->name('post-logout');

    Route::get('/get-logout', [AuthController::class, 'getLogout'])->name('get-logout');
    Route::get('/profile', [ProfileController::class, 'profilePage'])->name('profile');
    /** Check if profile fillled if not, force go to profile page */
    Route::middleware([ProfileFillIfEmpty::class])->group(function () {
        Route::get('/dashboard', [DashController::class, 'dashboardPage'])->name('dashboard');

        Route::middleware(['can:hasSuperPermission,App\Models\User'])->group(function () {
            Route::get('/user-man', [UserManController::class, 'userManPage'])->name('user-man');
            Route::get('/server-logs', [ServerManController::class, 'serverLogs'])->name('server-logs');
        });
    });
});
