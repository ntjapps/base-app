<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/** Route for login redirect */
Route::get('/login-redirect', function() {
  return redirect()->route('landing-page');
})->name('login');

/** Route start here, WEB used for GET only */
Route::get('/', function () {
    return view('welcome');
});

/** Routes that need authentication first */
Route::middleware(['auth','auth.session'])->group(function () {
});