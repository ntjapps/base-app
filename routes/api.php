<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** All API Route should be sanitized with XSS Middleware */
Route::middleware(['xss'])->group(function () {
  /** Get Constant */
  Route::middleware(['throttle:api-ext'])->group(function () {
    Route::post('/app-const', [AppConstController::class, 'mainConst'])->name('app-const');
  });
});
