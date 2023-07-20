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
    // users
    Route::get('users/{user}/conductors', [UsersController::class, 'conductors']);
    Route::get('users/{user}/balance', [UsersController::class, 'wallet_balance']);
    Route::get('users/{user}/deposits', [UsersController::class, 'deposits']);
    Route::get('users/{user}/cashouts', [UsersController::class, 'cashouts']);
    Route::patch('users/{user}', [UsersController::class, 'update']);

    // conductors
    Route::patch('conductors/{user}', [ConductorsController::class, 'update']);
    Route::post('conductors', [ConductorsController::class, 'store']);
    
    // cashout process
    Route::get('daraja/access_token', [CashoutsController::class, 'daraja_access_token']);
    Route::post('cashouts/otp', [CashoutsController::class, 'generate_otp']);
    Route::post('cashouts/init', [MpesaController::class, 'initiate_cashout']);
});
   
// callback urls
Route::post('deposits/validate', [MpesaController::class, 'validate_deposit']);
Route::post('deposits/store', [MpesaController::class, 'deposit']);
Route::post('cashouts/store', [MpesaController::class, 'cashout']);
