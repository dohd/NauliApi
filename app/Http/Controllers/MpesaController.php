<?php

namespace App\Http\Controllers;

use App\Models\Cashout;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpesaController extends Controller
{
    /**
     * Store Deposit
     * 
     */
    function deposit(Request $request)
    {
        $result = $this->dummyData()['deposit'];
            
        $data = [
            'trans_type' => $result['TransactionType'],
            'trans_id' => $result['TransID'],
            'trans_time' => $result['TransTime'],
            'trans_amount' => floatval($request->amount ?: $result['TransAmount']),
            'business_shortcode' => $result['BusinessShortCode'],
            'bill_ref_number' => $result['BillRefNumber'],
            'org_account_balance' => (float) $result['OrgAccountBalance'],
            'thirdparty_trans_id' => $result['ThirdPartyTransID'],
            'msisdn' => $result['MSISDN'],
            'first_name' => $result['FirstName'],
            'middle_name' => $result['MiddleName'],
            'last_name' => $result['LastName'],
        ];

        DB::beginTransaction();

        $user = User::where('username', $data['bill_ref_number'])->first();
        if ($user) $data['owner_id'] = $user->id;
        $deposit = Deposit::create($data);

        // compute wallet balance
        $last_cashout = Cashout::latest()->first();
        if ($last_cashout) {
            $deposit_amount = Deposit::where('created_at', '>', databaseTimestamp($last_cashout->created_at))->sum('trans_amount');
            $curr_amount = $last_cashout->wallet_balance + $deposit_amount;
            $net_balance = processNetBalance($curr_amount);
        } else {
            $curr_amount = Deposit::sum('trans_amount');
            $net_balance = processNetBalance($curr_amount);
        }
        $deposit->update([
            'wallet_balance' => $net_balance['amount'], 
            'service_fee' => $net_balance['fee']
        ]);

        DB::commit();
        
        return response()->json($deposit);
    }

    /**
     * Store Cashout
     * 
     */
    function cashout(Request $request)
    {
        // api call result
        $result = $this->dummyData()['cashout'];

        $data = [
            'conversation_id' => $result['ConversationId'],
            'origin_conversation_id' => $result['OriginatorConversationId'],
            'result_desc' => $result['ResultDesc'],
            'result_type' => $result['ResultType'],
            'result_code' => $result['ResultCode'],
            'trans_id' => $result['TransactionID'],
            'trans_receipt' => $result['TransactionReceipt'],
            'trans_amount' => floatval($request->amount ?: $result['TransactionAmount']),
            'working_account_balance' => (float) $result['B2CWorkingAccountAvailableFunds'],
            'utility_account_balance' => (float) $result['B2CUtilityAccountAvailableFunds'],
            'trans_completed_datetime' => $result['TransactionCompletedDateTime'],
            'recepient_name' => $result['ReceiverPartyPublicName'],
            'is_registered_customer' => $result['B2CRecipientIsRegisteredCustomer'],
        ];

        DB::beginTransaction();

        $recepient_name = explode('-', $data['recepient_name']);
        if (@$recepient_name[0]) {
            $phone = trim($recepient_name[0]);
            $user = User::where('phone', $phone)->first();
            if ($user) $data['owner_id'] = $user->id;
        }
        $cashout = Cashout::create($data);
        
        // compute wallet balance
        $last_deposit = Deposit::where('created_at', '<=', databaseTimestamp($cashout->created_at))->latest()->first();
        $net_balance = processNetBalance($cashout->trans_amount);
        $wallet_balance = $last_deposit->wallet_balance - $net_balance['amount'] - $net_balance['fee'];
        $cashout->update([
            'wallet_balance' => $wallet_balance, 
            'service_fee' => $net_balance['fee']
        ]);

        DB::commit();
            
        return response()->json($cashout);
    }

    public function dummyData() {
        return [
            'deposit' => [
                'TransactionType' => 'Pay Bill',
                'TransID' => 'RKTQDM7W6S',
                'TransTime' => '20191122063845',
                'TransAmount' => '100',
                'BusinessShortCode' => '600638',
                'BillRefNumber' => 'john_doe',
                'OrgAccountBalance' => '',
                'ThirdPartyTransID' => '',
                'MSISDN' => '2547' . rand(10000000, 99999999),
                'FirstName' => 'Marcus',
                'MiddleName' => '',
                'LastName' => 'Garvey',
            ],
            'cashout' => [
                'ConversationId' => ' 236543-276372-2',
                'OriginatorConversationId' => ' AG_2376487236_126732989KJHJKH ',
                'ResultDesc' => 'Service request is has bee accepted successfully',
                'ResultType' => '0',
                'ResultCode' => '0',
                'TransactionID' => 'LHG31AA5TX',
                'TransactionReceipt' => 'LHG31AA5TX',
                'TransactionAmount' => '400',
                'B2CWorkingAccountAvailableFunds' => '20000',
                'B2CUtilityAccountAvailableFunds' => ' 25000',
                'TransactionCompletedDateTime' => '01.08.2018 16:12:12',
                'ReceiverPartyPublicName' => '254720020022 - John Doe',
                'B2CRecipientIsRegisteredCustomer' => 'Y',
            ]
        ];
    }
}
