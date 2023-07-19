<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Cashout;
use App\Models\User;
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
     * Initiate Cashout
     * 
     */
    public function initiate_cashout(Request $request) {
        $request->validate(['amount' => 'required']);

        // verify otp
        // if ($request->otp != auth()->user()->withdraw_otp) throw new CustomException('Invalid OTP code.');
        // $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime(auth()->user()->withdraw_otp_exp);
        // if ($exp_diff > 0) throw new CustomException('Expired OTP code.');
        
        $user = User::find(auth()->user()->owner_id);
        $phone = $user->phone;
        $response = $this->businessPayment($request->amount, 254708374149);
        Cashout::create([
            'owner_id' => $user->id,
            'conversation_id' => $response['ConversationID'],
            'origin_conversation_id' => $response['OriginatorConversationID'],
        ]);
        
        return response()->json([
            'message' => 'Cashout process initiated successfully',
            'data' => $response,
        ]);
    }
}
