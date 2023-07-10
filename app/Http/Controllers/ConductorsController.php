<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ConductorsController extends Controller
{
    /**
     * Store Conductor Resource
     */
    public function store(Request $request) 
    {
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
    }

    /**
     * Update Conductor Resource
     * 
     */
    public function update(Request $request, User $user) 
    {
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
    }

    /**
     * 
     */
}
