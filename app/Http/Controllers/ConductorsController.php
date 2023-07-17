<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ConductorsController extends Controller
{
    /**
     * Store Conductor Resource
     */
    public function store(Request $request) 
    {
        $request->validate(['name' => 'required', 'phone' => 'required']);
        $name = $request->name;
        $phone = $request->phone;

        if (Auth::user()->rel_id) throw new CustomException('Unauthorized!', 401);
        
        $phone_exists = User::where('phone', $phone)->exists();
        if ($phone_exists) throw new CustomException('Phone Number exists! Try a different number.', 409);

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
            if ($username_exists) $username = strtolower($username[1][0]) . $mod_1 . rand(10, 999);
        } 
        if (is_array($username)) $username = strtolower($username[0]);

        // generate random password
        $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
        $password = substr(str_shuffle($chars), 0, 5);

        $user = User::create([
            'name' => $name,
            'phone' => $phone,
            'username' => $username,
            'password' => $password,
        ]);

        // send password via sms service

        return response()->json(['message' => 'User created successfully', 'user_id' => $user->id]);
    }

    /**
     * Update Conductor Resource
     * 
     */
    public function update(Request $request, User $user) 
    {
        $name = $request->name;
        $phone = $request->phone;
        $username = $request->username;
        $password = $request->password;
        
        if ($name) {
            if ($user->rel_id) throw new CustomException('Unauthorized', 401);
            $updated = $user->update(compact('name'));
        } elseif ($phone) {
            if ($user->rel_id) throw new CustomException('Unauthorized', 401);
            $exists = User::where('id', '!=', $user->id)->where('phone', $phone)->exists();
            if ($exists) throw new CustomException('Phone Number exists! Try a different phone number.', 409);
            $updated = $user->update(compact('phone'));
        } elseif ($username) {
            $exists = User::where('id', '!=', $user->id)->where('username', $username)->exists();
            if ($exists) throw new CustomException('Username exists! Try a different username.', 409);
            $updated = $user->update(compact('username'));
        } elseif ($password) {
            $updated = $user->update(compact('password'));
        }
        
        return response()->json(['message' => 'User updated successfully']);
    }
}
