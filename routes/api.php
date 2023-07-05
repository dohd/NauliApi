<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('login', function (Request $request) {
    if (Auth::attempt(['email' => 'john@gmail.com', 'password' => '1234'])) {
        $user = Auth::user();
        $success['token'] = $user->createToken(config('app.name'))->accessToken;
        return response()->json(compact('success'));
    }
    return response()->json(['error' => 'Unauthorized'], 401);
});

Route::post('register', function (Request $request) {
    $user = new User;
    $success['token'] = $user->createToken(config('app.name'))->accessToken;
    return response()->json(compact('success'));
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::post('details', function (Request $request) {
        $user = Auth::user();
        return response()->json(compact('user'));
    });
});
