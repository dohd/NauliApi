<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConductorsController;
use App\Http\Controllers\MpesaController;
use App\Http\Controllers\UsersController;

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

// authentication
Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('password/forgot', [AuthController::class, 'password_forgot_otp']);
Route::post('password/reset', [AuthController::class, 'password_reset']);

Route::group(['middleware' => 'auth:api'], function () {
    // users
    Route::get('users/{user}/conductors', [UsersController::class, 'user_conductors']);
    Route::get('users/{user}/balance', [UsersController::class, 'account_balance']);
    Route::get('users/{user}/deposits', [UsersController::class, 'user_deposits']);
    Route::get('users/{user}/withdrawals', [UsersController::class, 'user_withdrawals']);
    Route::post('users/{user}/withdrawals/otp', [UsersController::class, 'withdrawal_otp']);
    Route::post('users/{user}/withdrawals/confirm', [UsersController::class, 'confirm_withdrawal']);
    Route::patch('users/{user}', [UsersController::class, 'update']);

    // conductors
    Route::patch('conductors/{user}', [ConductorsController::class, 'update']);
    Route::post('conductors', [ConductorsController::class, 'store']);

    // daraja api callbacks
    Route::post('deposits/store', [MpesaController::class, 'store_deposit']);
    Route::post('withdrawals/store', [MpesaController::class, 'store_withdrawal']);
});
