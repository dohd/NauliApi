<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashoutsController;
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
    // logout
    Route::post('logout', [AuthController::class, 'logout']);

    // users
    Route::get('users/{user}/conductors', [UsersController::class, 'conductors']);
    Route::get('users/{user}/balance', [UsersController::class, 'wallet_balance']);
    Route::get('users/{user}/deposits', [UsersController::class, 'deposits']);
    Route::get('users/{user}/cashouts', [UsersController::class, 'cashouts']);
    Route::get('users/{user}', [UsersController::class, 'show']);
    Route::patch('users/{user}', [UsersController::class, 'update']);

    // conductors
    Route::patch('conductors/{user}', [ConductorsController::class, 'update']);
    Route::post('conductors/status', [ConductorsController::class, 'update_status']);
    Route::post('conductors', [ConductorsController::class, 'store']);
    
    // cashout process
    Route::get('daraja_access_token', [CashoutsController::class, 'daraja_access_token']);
    Route::post('cashouts/otp', [CashoutsController::class, 'cashout_otp']);
    Route::post('cashouts/init', [MpesaController::class, 'initiate_cashout']);
});
   
// callback urls
Route::post('deposits/validate', [MpesaController::class, 'validate_deposit']);
Route::post('deposits/confirm', [MpesaController::class, 'deposit']);
Route::post('cashouts/confirm', [MpesaController::class, 'cashout']);
Route::post('transaction_status', [MpesaController::class, 'transaction_status']);
Route::post('transaction_status/result', [MpesaController::class, 'status_result']);
