<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Http\Request;

class MpesaController extends Controller
{
    /**
     * Deposit Confirmation
     * 
     */
    function store_deposit(Request $request)
    {
        try {
            // api call result
            $result = [
                'TransactionType' => 'Pay Bill',
                'TransID' => 'RKTQDM7W6S',
                'TransTime' => '20191122063845',
                'TransAmount' => '100',
                'BusinessShortCode' => '600638',
                'BillRefNumber' => 'john_doe',
                'OrgAccountBalance' => '',
                'ThirdPartyTransID' => '',
                'MSISDN' => '254710010011',
                'FirstName' => 'Marcus',
                'MiddleName' => '',
                'LastName' => 'Garvey',
            ];
    
            $data = [
                'trans_type' => $result['TransactionType'],
                'trans_id' => $result['TransID'],
                'trans_time' => $result['TransTime'],
                'trans_amount' => (float) $result['TransAmount'],
                'business_shortcode' => $result['BusinessShortCode'],
                'bill_ref_number' => $result['BillRefNumber'],
                'org_account_balance' => (float) $result['OrgAccountBalance'],
                'thirdparty_trans_id' => $result['ThirdPartyTransID'],
                'msisdn' => $result['MSISDN'],
                'first_name' => $result['FirstName'],
                'middle_name' => $result['MiddleName'],
                'last_name' => $result['LastName'],
            ];
    
            $user = User::where('username', $data['bill_ref_number'])->first();
            if ($user) $data['owner_id'] = $user->id;
            $deposit = Deposit::create($data);
    
            return response()->json(['message' => 'Deposit created successfully', 'owner_id' => $deposit->owner_id]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Withdrawal Confirmation
     * 
     */
    function store_withdrawal(Request $request)
    {
        try {
            // api call result
            $result = [
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
                'ReceiverPartyPublicName' => '2540720020022 - John Doe',
                'B2CRecipientIsRegisteredCustomer' => 'Y',
            ];
    
            $data = [
                'conversation_id' => $result['ConversationId'],
                'origin_conversation_id' => $result['OriginatorConversationId'],
                'result_desc' => $result['ResultDesc'],
                'result_type' => $result['ResultType'],
                'result_code' => $result['ResultCode'],
                'trans_id' => $result['TransactionID'],
                'trans_receipt' => $result['TransactionReceipt'],
                'trans_amount' => (float) $result['TransactionAmount'],
                'working_account_balance' => (float) $result['B2CWorkingAccountAvailableFunds'],
                'utility_account_balance' => (float) $result['B2CUtilityAccountAvailableFunds'],
                'trans_completed_datetime' => $result['TransactionCompletedDateTime'],
                'recepient_name' => $result['ReceiverPartyPublicName'],
                'is_registered_customer' => $result['B2CRecipientIsRegisteredCustomer'],
            ];
    
            $recepient_name = explode('-', $data['recepient_name']);
            if (@$recepient_name[0]) {
                $phone = trim($recepient_name[0]);
                $user = User::where('phone', $phone)->first();
                if ($user) $data['owner_id'] = $user->id;
            }
            $withdrawal = Withdrawal::create($data);
    
            return response()->json(['message' => 'Withdrawal created successfully', 'owner_id' => $withdrawal->owner_id]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
