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
    public function cashout_otp(Request $request) 
    {     
        if (Auth::user()->rel_id) throw new CustomException('Unauthorized', 401);

        // otp expiry 180 seconds
        $otp = rand(100000, 999999);
        Auth::user()->update([
            'withdraw_otp' => $otp,
            'withdraw_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 180),
        ]); 

        // send otp to sms service

        return response()->json([
            'message' => 'OTP Generated successfully',
            'data' => compact('otp'),
        ]);
    }

    /**
     * Daraja API Access Token
     */
    public function daraja_access_token(Request $request) 
    {
        $response = $this->getAccessToken();
        return response()->json($response);
    }
}
