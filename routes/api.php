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
    $request->validate([
        'username' => 'required',
        'password' => 'required',
    ]);

    $input = $request->only('username', 'password');

    try {
        $phone_attempt = Auth::attempt(['phone' => trim($input['username']), 'password' => $input['password']]);
        $username_attempt = Auth::attempt(['username' => trim($input['username']), 'password' => $input['password']]);
        if (!$phone_attempt && !$username_attempt) {
            trigger_error('Invalid login details!');
        }

        $user = Auth::user();
        $success['token'] = $user->createToken(config('app.name'))->accessToken;
        return response()->json(compact('success'));
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 401);
    }    
});

// register user
Route::post('register', function (Request $request) {
    $request->validate([
        'name' => 'required',
        'phone' => 'required',
        'password' => 'required',
    ]);

    $input = $request->only('name', 'phone', 'password');

    try {
        $phone_exists = User::where('phone', $input['phone'])->exists();
        if ($phone_exists) {
            $error_status = 409;
            trigger_error('Phone Number exists! Try a different number.');
        }

        // generate random username
        $username = explode(' ', trim($input['name']));
        $username_exists = User::where('username', strtolower($username[0]))->exists();
        if ($username_exists) {
            foreach (range(0, 10000) as $n) {
                $mod_1 = strtolower($username[0]) . rand(10, 999);
                $username_exists = User::where('username', $mod_1)->exists();
                if (!$username_exists) {
                    $username = $mod_1;
                    break;
                }
            }
            if ($username_exists) 
                $username = strtolower($username[1][0]) . $mod_1 . rand(10, 999);
        } 
        if (is_array($username)) $username = strtolower($username[0]);

        // generate random password
        // $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
        // $password = substr(str_shuffle($chars), 0, 5);
        // error_log(print_r($password, 1));

        // send otp to phone via sms service
        $otp = rand(100000, 99999);

        // on success create user
        $user = User::create([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'username' => $username,
            'password' => $input['password'],
        ]);

        $register['token'] = $user->createToken(config('app.name'))->accessToken;
        return response()->json($register);
    } catch (\Throwable $th) {
        // log error
        return response()->json(['error' => $th->getMessage()], @$error_status ?: 500);
    }
});

// password pre-reset OTP code
Route::post('password/forgot', function (Request $request) {
    $request->validate(['username' => 'required']);
    $input = $request->username;

    try {
        $user = User::where('username', $input['username'])
            ->orWhere('phone', $input['username'])->first();
        if (!$user) trigger_error('Username / Phone Number could not be found!');

        // otp expiry 120 seconds
        $user->update([
            'pass_reset_otp' => rand(100000, 999999), 
            'reset_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 120),
        ]);

        // send otp using sms service
        
        return response()->json(['message' => 'OTP generated successfully']);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 401);
    }
});

// password reset
Route::post('password/reset', function (Request $request) {
    $request->validate(['password' => 'required', 'otp' => 'required']);
    $password = $request->password;
    $otp = $request->otp;

    try {
        $user = User::where('pass_reset_otp', $otp)->first();
        if (!$user) trigger_error('Invalid OTP code!');

        // verify otp expiry
        $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime($user->pass_otp_exp);
        if ($exp_diff > 0) trigger_error('Expired OTP code.');
        
        $user->update(['password' => $password]);
    
        return response()->json(['message' => 'Password reset successfully']);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 401);
    }
});

Route::group(['middleware' => 'auth:api'], function () {
    // update user
    Route::patch('users/{user}', function(Request $request, User $user) {
        if (!$user->id || $user->rel_id) {
            $error_status = 401;
            trigger_error('Unauthorized');
        }

        $name = $request->name;
        $username = $request->username;
        $phone = $request->phone;
        $password = $request->password;

        try {
            if ($username) {
                $exists = User::where('id', '!=', $user->id)
                    ->where('username', 'LIKE', "%{$username}%")->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Username exists! Try a different name.');
                }
                $updated = $user->update(compact('username'));
            } elseif ($phone) {
                $exists = User::where('id', '!=', $user->id)
                    ->where('phone', $phone)->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Phone Number exists! Try a different number.');
                }
                $updated = $user->update(compact('phone'));
            } elseif ($name) {
                $updated = $user->update(compact('name'));
            } elseif ($password) {
                $updated = $user->update(compact('password'));
            }

            return response()->json(['message' => 'User updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], @$error_status ?: 500);
        }
    });

    // store conductor
    Route::post('conductors', function (Request $request) {
        $request->validate(['user_id' => 'required']);

        $user_id = $request->user_id;
        $name = $request->name;
        $phone = $request->phone;

        try {
            $main_user = User::find($user_id);
            if (!$main_user) {
                $error_status = 401;
                trigger_error('Unauthorized');
            }

            $phone_exists = User::where('phone', $phone)->exists();
            if ($phone_exists) {
                $error_status = 409;
                trigger_error('Phone Number exists! Try a different number.');
            }

            // generate random username
            $username = explode(' ', trim($name));
            $username_exists = User::where('username', strtolower($username[0]))->exists();
            if ($username_exists) {
                foreach (range(0, 10000) as $n) {
                    $mod_1 = strtolower($username[0]) . rand(10, 999);
                    $username_exists = User::where('username', $mod_1)->exists();
                    if (!$username_exists) {
                        $username = $mod_1;
                        break;
                    }
                }
                if ($username_exists) 
                    $username = strtolower($username[1][0]) . $mod_1 . rand(10, 999);
            } 
            if (is_array($username)) $username = strtolower($username[0]);

            // generate random password
            $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
            $password = substr(str_shuffle($chars), 0, 5);
            // error_log(print_r($password, 1));

            $user = User::create([
                'name' => $name,
                'phone' => $phone,
                'username' => $username,
                'password' => $password,
            ]);

            if ($user) {
                // send password via sms service

            }

            return response()->json(['message' => 'User created successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], @$error_status ?: 500);
        }
    });

    // update conductor
    Route::patch('conductors/{user}', function(Request $request, User $user) {
        if (!$user->id || !$user->rel_id) {
            $error_status = 401;
            trigger_error('Unauthorized');
        }

        $name = $request->name;
        $phone = $request->phone;
        $username = $request->username;
        $password = $request->password;
        
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
                $exists = User::where('id', '!=', $user->id)
                    ->where('phone', $phone)->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Phone Number exists! Try a different phone number.');
                }
                $updated = $user->update(compact('phone'));
            } elseif ($username) {
                $exists = User::where('id', '!=', $user->id)
                    ->where('username', $username)->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Username exists! Try a different username.');
                }
                $updated = $user->update(compact('username'));
            } elseif ($password) {
                $updated = $user->update(compact('password'));
            }
            
            return response()->json(['message' => 'User updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], @$error_status ?: 500);
        }
    });

    // fetch user conductors
    Route::get('users/{user_id}/conductors', function (Request $request, $user_id) {
        $users = User::where('rel_id', $user_id)->get();

        $users = [
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
        $users = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'username' => $v,
            'phone' => '07' . rand(10000000, 99999999),
            'active' => rand(0,1),
        ], $users);

        return response()->json($users);
    });

    // fetch user account balance
    Route::get('users/{user_id}/balance', function (Request $request, $user_id) {
        $user = User::find($user_id);

        $balance = [
            'amount' => number_format(20000, 2), 
            'currency' => 'Ksh. '
        ];

        // trigger B2C transaction in daraja api

        return response()->json($balance);
    });

    // fetch user deposits
    Route::get('users/{user_id}/deposits', function (Request $request, $user_id) {
        $user = User::find($user_id);

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

        // trigger B2C transaction in daraja api

        return response()->json($deposits);
    });

    // fetch account withdrawals
    Route::get('users/{user_id}/withdrawals', function (Request $request, $user_id) {
        $user = User::find($user_id);

        $withdrawals = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'amount' => number_format(100, 2),
            'currency' => 'Ksh.',
            'date' => date('d-m-Y'),
            'time' => date('g:i a'),
        ], range(1, 20));

        // trigger B2C transaction in daraja api

        return response()->json($withdrawals);
    });

    // generate pre-withdrawal OTP code
    Route::post('withdrawals/otp', function(Request $request) {
        $request->validate(['user_id' => 'required']);

        try {
            $user = User::find($request->user_id);
            if (!$user) trigger_error('Unauthorized!');

            // otp expiry 120 seconds
            $user->update([
                'withdraw_otp' => rand(100000, 999999), 
                'withdraw_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 120),
            ]);
            
            // send otp using sms service

            return response()->json(['message' => 'OTP Generated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 401);
        }
    });

    // confirm amount withdrawal
    Route::post('withdrawals/confirm', function(Request $request) {
        $request->validate([
            'user_id' => 'required',
            'amount' => 'required',
            'otp' => 'required',
        ]);
        $input = $request->only('user_id', 'amount', 'otp');

        try {
            $user = User::find($input['user_id']);
            if (!$user) trigger_error('Unauthorized!');

            // verify otp expiry
            $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime($user->withdraw_otp_exp);
            if ($exp_diff > 0) trigger_error('Expired OTP code.');

            // trigger B2C transaction in daraja api

            return response()->json(['message' => 'Withdrawal transaction processed successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 401);
        }
    });
});
