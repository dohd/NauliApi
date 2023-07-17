<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
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
        $request->validate(['username' => 'required','password' => 'required']);
        $input = $request->only('username', 'password');
    
        $phone_attempt = Auth::attempt(['phone' => trim($input['username']), 'password' => $input['password']]);
        $username_attempt = Auth::attempt(['username' => trim($input['username']), 'password' => $input['password']]);
        if (!$phone_attempt && !$username_attempt) throw new CustomException('Invalid login details!', 401);
        $user = Auth::user();
        $success['token'] = $user->createToken(config('app.name'))->accessToken;
        
        return response()->json(compact('success'));
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
    
        $phone_exists = User::where('phone', $input['phone'])->exists();
        if ($phone_exists) throw new CustomException('Phone Number exists! Try a different number.', 409);

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
            if ($username_exists) $username = strtolower($username[1][0]) . $mod_1 . rand(10, 999);
        } 
        if (is_array($username)) $username = strtolower($username[0]);

        // generate random password
        $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
        $password = substr(str_shuffle($chars), 0, 5);

        // send password to phone via sms service

        // on success create user
        $user = User::create([
            'name' => $input['name'],
            'phone' => $input['phone'],
            'username' => $username,
            'password' => $input['password'],
        ]);
        $success['token'] = $user->createToken(config('app.name'))->accessToken;

        return response()->json($success);
    }

    /**
     * Password Forgot Preset-OTP
     * 
     */
    public function password_forgot_otp(Request $request) 
    {
        $request->validate(['username' => 'required']);
    
        $user = User::where('username', $request->username)
            ->orWhere('phone', $request->username)->first();
        if (!$user) throw new CustomException('Username or Phone Number could not be found!', 401);

        // otp expiry 120 seconds
        $user->update([
            'pass_reset_otp' => rand(100000, 999999), 
            'reset_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 120),
        ]);

        // send otp using sms service
        
        return response()->json(['message' => 'OTP generated successfully']);
    }

    /**
     * Password Reset
     * 
     */
    public function password_reset(Request $request) 
    {
        $request->validate(['password' => 'required', 'otp' => 'required']);
    
        $user = User::where('pass_reset_otp', $request->otp)->first();
        if (!$user) throw new CustomException('Invalid OTP code!');

        // verify otp expiry
        $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime($user->pass_otp_exp);
        if ($exp_diff > 0) throw new CustomException('Expired OTP code.');
        
        $user->update(['password' => $request->password]);
    
        return response()->json(['message' => 'Password reset successfully']);
    }
}
