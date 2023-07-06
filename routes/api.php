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

// login user
Route::post('login', function (Request $request) {
    $password = $request->password;
    // break input into username or password
    $input = $request->username;
    $username = '';
    $phone = '';

    if ($username) {
        // 
    } elseif ($phone) {
        // 
    }

    if (Auth::attempt(['name' => 'John Doe', 'password' => '1234'])) {
        $user = Auth::user();
        $success['token'] = $user->createToken(config('app.name'))->accessToken;
        return response()->json(compact('success'));
    }
    
    return response()->json(['error' => 'Unauthorized'], 401);
});

// register user
Route::post('register', function (Request $request) {
    $name = $request->name;
    $phone = $request->phone;
    $error_status = 500;

    try {
        $phone_exists = User::where('phone', $phone)->exists();
        if ($phone_exists) {
            $error_status = 409;
            trigger_error('Phone Number exists! Try a different number.');
        }

        // generate unique username
        $username = str_replace(' ', '_', strtolower($name)) . rand(100, 999);
        // generate unique password
        $password = $phone;

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'username' => $username,
            'password' => $password,
        ]);

        // send password to phone via sms service

        $register['user'] = $user; 
        $register['token'] = $user->createToken(config('app.name'))->accessToken;
        return response()->json($register);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], $error_status);
    }
});

// password forgot pin
Route::post('password/forgot', function (Request $request) {
    $input = $request->username;
    // break input into username or password
    $input = $request->username;
    $username = '';
    $phone = '';

    if ($username) {
        // generate otp
    } elseif ($phone) {
        // generate otp 
    }

    // send otp using sms service
    
    return response()->json(['success' => 1]);
});

// password reset
Route::post('password/reset', function (Request $request) {
    $password = $request->password;
    $otp = $request->otp;
    try {
        // decrypt otp

        // update user loaded in otp

    } catch (\Throwable $th) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    return response()->json(['success' => 1]);
});

Route::group(['middleware' => 'auth:api'], function () {
    // update user
    Route::patch('users/{user}', function(Request $request, User $user) {
        $name = $request->name;
        $phone = $request->phone;
        $username = $request->username;
        $password = $request->password;
        $error_status = 500;
        try {
            if ($user->rel_id) {
                $error_status = 401;
                trigger_error('Unauthorized');
            }

            if ($username) {
                $username_exists = User::where('id', '!=', $user->id)
                    ->where('username', 'LIKE', "%{$username}%")->exists();
                if ($username_exists) {
                    $error_status = 409;
                    trigger_error('Username exists! Try a different name.');
                }
                $updated = $user->update(compact('username'));
            } elseif ($phone) {
                $phone_exists = User::where('id', '!=', $user->id)
                    ->where('phone', $phone)->exists();
                if ($phone_exists) {
                    $error_status = 409;
                    trigger_error('Phone Number exists! Try a different number.');
                }
            } elseif ($name) {
                $updated = $user->update(compact('name'));
            } elseif ($password) {
                $updated = $user->update(compact('password'));
            }

            return response()->json(compact('updated'));
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $error_status);
        }
    });

    // store conductor
    Route::post('conductors', function (Request $request) {
        $user_id = $request->user_id;
        $name = $request->name;
        $phone = $request->phone;
        $error_status = 500;

        try {
            $is_conductor = User::where('id', $user_id)->whereNotNull('rel_id')->exists();
            if ($is_conductor) {
                $error_status = 401;
                trigger_error('Unauthorized');
            }

            $phone_exists = User::where('phone', $phone)->exists();
            if ($phone_exists) {
                $error_status = 409;
                trigger_error('Phone Number exists! Try a different number.');
            }

            // generate unique username
            $username = str_replace(' ', '_', strtolower($name)) . rand(100, 999);
            // generate unique password
            $password = $phone;

            $conductor = User::create([
                'name' => $name,
                'phone' => $phone,
                'username' => $username,
                'password' => $password,
            ]);

            // send password to phone via sms service

            return response()->json(compact('success'));
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $error_status);
        }
    });

    // update conductor
    Route::patch('conductors/{user}', function(Request $request, User $user) {
        $name = $request->name;
        $phone = $request->phone;
        $username = $request->username;
        $password = $request->password;
        $error_status = 500;
        try {
            if ($name) {
                if ($user->rel_id) {
                    $error_status = 401;
                    trigger_error('Unauthorized');
                }
                $updated = $user->update(compact('name'));
            } elseif ($phone) {
                if ($user->rel_id) {
                    $error_status = 401;
                    trigger_error('Unauthorized');
                }
                $phone_exists = User::where('id', '!=', $user->id)
                    ->where('phone', $phone)->exists();
                if ($phone_exists) {
                    $error_status = 409;
                    trigger_error('Phone Number exists! Try a different number.');
                }
            } elseif ($username) {
                $username_exists = User::where('id', '!=', $user->id)
                    ->where('username', 'LIKE', "%{$username}%")->exists();
                if ($username_exists) {
                    $error_status = 409;
                    trigger_error('Username exists! Try a different name.');
                }
                $updated = $user->update(compact('username'));
            } elseif ($password) {
                $updated = $user->update(compact('password'));
            }
            
            return response()->json(compact('updated'));
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], $error_status);
        }
    });

    // fetch user conductors
    Route::get('conductors', function (Request $request) {
        $conductors = User::where('rel_id', $request->user_id)->get();

        $conductors = [
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
        ];
        $conductors = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'username' => $v,
            'phone' => '07' . rand(10000000, 99999999),
            'active' => rand(0,1),
        ], $conductors);

        return response()->json($conductors);
    });

    // fetch user account balance
    Route::get('account_balance', function (Request $request, User $user) {
        $balance = ['amount' => number_format(20000, 2), 'currency' => 'Ksh. '];

        // fetch balance from daraja api 

        return response()->json($balance);
    });

    // fetch user deposits
    Route::get('deposits', function (Request $request) {
        $deposits = [
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
        ];
        $deposits = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'name' => $v,
            'phone' => '07' . rand(10000000, 99999999),
            'amount' => number_format(100, 2),
            'currency' => 'Ksh.',
            'date' => date('d-m-Y'),
            'time' => date('g:i a'),
        ], $deposits);

        // fetch deposits from daraja api

        return response()->json($deposits);
    });

    // fetch account withdrawals
    Route::get('withdrawals', function (Request $request) {
        $withdrawals = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'amount' => number_format(100, 2),
            'currency' => 'Ksh.',
            'date' => date('d-m-Y'),
            'time' => date('g:i a'),
        ], range(1, 20));

        // fetch withdrawals from daraja api

        return response()->json($withdrawals);
    });

    // generate pre-withdrawal pin
    Route::post('withdrawals/otp', function(Request $request) {
        $user = User::find($request->user_id);
        
        // generate otp

        // send otp using sms service

        return response()->json(['success' => 1]);
    });

    // confirm amount withdrawal
    Route::post('withdrawals/confirm', function(Request $request) {
        $user_id = $request->user_id;
        $otp = $request->otp;
        $amount = $request->amount;

        $user = User::find($request->user_id);

        // api call to daraja api to confirm withdrawal

        return response()->json(['success' => 1]);
    });
});
