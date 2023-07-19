<?php

namespace App\Http\Controllers;

use App\Models\Cashout;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpesaController extends Controller
{
    /**
     * Store Cashout
     * 
     */
    function cashout(Request $request)
    {
        $result = $request->Result;
        $result_params = $result['ResultParameters']['ResultParameter'];

        $data = [
            'origin_conversation_id' => $result['OriginatorConversationID'],
            'conversation_id' => $result['ConversationID'],
            'trans_id' => $result['TransactionID'],
        ];
        foreach ($result_params as $param) {
            $key = $param['Key'];
            $value = $param['Value']; 
            switch ($key) {
                case 'TransactionAmount': $data['trans_amount'] = $value;
                case 'TransactionReceipt': $data['trans_receipt'] = $value;
                case 'ReceiverPartyPublicName': $data['recepient_name'] = $value;
                case 'TransactionCompletedDateTime': $data['trans_completed_datetime'] = $value;
                case 'B2CUtilityAccountAvailableFunds': $data['utility_account_balance'] = $value;
                case 'B2CWorkingAccountAvailableFunds': $data['working_account_balance'] = $value;
                case 'B2CRecipientIsRegisteredCustomer': $data['is_registered_customer'] = $value;
                case 'B2CChargesPaidAccountAvailableFunds': $data['charges_paid_account_balance'] = $value;
            }
        }

        DB::beginTransaction();

        $cashout = Cashout::where('conversation_id', $data['conversation_id'])->first();
        $cashout->update($data);
        
        // compute wallet balance
        $data = [
            'owner_id' => $cashout->owner_id,
            'cashout_id' => $cashout->id,
            'cashout' => $cashout->trans_amount,
            'balance' => $cashout->trans_amount,
            'fee' => processNetBalance($cashout->trans_amount)['fee'],
            'net_balance' => processNetBalance($cashout->trans_amount)['amount'],
        ];
        $last_transaction = Transaction::latest()->first();
        if ($last_transaction) {
            $data['balance'] = $last_transaction->balance - $data['cashout'];
            $data['fee'] = processNetBalance($data['balance'])['fee'];
            $data['net_balance'] = processNetBalance($data['balance'])['amount'];
            Transaction::create($data);
        } else {
            Transaction::create($data);
        }

        DB::commit();
            
        return response()->json($cashout);
    }

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
        $data = [
            'owner_id' => $deposit->owner_id,
            'deposit_id' => $deposit->id,
            'deposit' => $deposit->trans_amount,
            'balance' => $deposit->trans_amount, 
            'fee' => processNetBalance($deposit->trans_amount)['fee'],
            'net_balance' => processNetBalance($deposit->trans_amount)['amount'],
        ];
        $last_transaction = Transaction::latest()->first();
        if ($last_transaction) {
            $data['balance'] = $last_transaction->balance + $data['deposit'];
            $data['fee'] = processNetBalance($data['balance'])['fee'];
            $data['net_balance'] = processNetBalance($data['balance'])['amount'];
            Transaction::create($data);
        } else {
            Transaction::create($data);
        }
         
        DB::commit();
        
        return response()->json($deposit);
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
                'ConversationID' => 'AG_2376487236_126732989KJHJKH',
                'OriginatorConversationID' => '236543-276372-2',
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
