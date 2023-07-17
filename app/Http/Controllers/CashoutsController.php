<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashoutsController extends Controller
{
    use DarajaApi;

    /**
     * Cashout pre-confirmation OTP code
     * 
     */
    public function generate_otp(Request $request) 
    {     
        $user = Auth::user();
        if ($user->rel_id) throw new CustomException('Unauthorized');

        // otp expiry 180 seconds
        $user->update([
            'withdraw_otp' => rand(100000, 999999), 
            'withdraw_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 180),
        ]); 

        // send otp using sms service


        return response()->json(['message' => 'OTP Generated successfully']);
    }


    /**
     * Process Cashout
     * 
     */
    public function process_cashout(Request $request) {
        $request->validate(['amount' => 'required', 'otp' => 'required']);

        // verify otp
        // if ($request->otp != auth()->user()->withdraw_otp) throw new CustomException('Invalid OTP code.');
        // $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime(auth()->user()->withdraw_otp_exp);
        // if ($exp_diff > 0) throw new CustomException('Expired OTP code.');
        
        // trigger B2C transaction (business payment)
        $this->businessPayment($request->amount);

        return response()->json(['message' => 'Cashout process initiated successfully']);
    }
}
