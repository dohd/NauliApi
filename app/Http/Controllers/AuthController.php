<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Login
     * 
     */
    public function login(Request $request) 
    {
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
    }

    /**
     * Register
     * 
     */
    public function register(Request $request) 
    {
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
    }

    /**
     * Password Forgot Preset-OTP
     * 
     */
    public function password_forgot_otp(Request $request) 
    {
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
    }

    /**
     * Password Reset
     * 
     */
    public function password_reset(Request $request) 
    {
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
    }
}
