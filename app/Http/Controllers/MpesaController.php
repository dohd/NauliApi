<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Cashout;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MpesaController extends Controller
{
    use DarajaApi;

    /**
     * Initiate Cashout
     * 
     */
    public function initiate_cashout(Request $request) 
    {
        $request->validate(['amount' => 'required']);
        
        // verify otp
        // if ($request->otp_code != auth()->user()->withdraw_otp) throw new CustomException('Invalid OTP code.');
        // $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime(auth()->user()->withdraw_otp_exp);
        // if ($exp_diff > 0) throw new CustomException('Expired OTP code.');
        
        if (!password_verify($request->password, auth()->user()->password))
            throw new CustomException('Invalid password!', 401);

        // $response = $this->businessPayment($request->amount, $user->phone);
        // Cashout::create([
        //     'owner_id' => $user->id,
        //     'conversation_id' => $response['ConversationID'],
        //     'origin_conversation_id' => $response['OriginatorConversationID'],
        // ]);
        
        return response()->json([
            'message' => 'Cashout process initiated successfully',
            // 'data' => $response,
        ]);
    }
    
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
                case 'TransactionAmount': $data['trans_amount'] = (float) $value;
                case 'TransactionReceipt': $data['trans_receipt'] = $value;
                case 'ReceiverPartyPublicName': $data['recepient_name'] = $value;
                case 'TransactionCompletedDateTime': $data['trans_completed_datetime'] = $value;
                case 'B2CUtilityAccountAvailableFunds': $data['utility_account_balance'] = (float) $value;
                case 'B2CWorkingAccountAvailableFunds': $data['working_account_balance'] = (float) $value;
                case 'B2CRecipientIsRegisteredCustomer': $data['is_registered_customer'] = $value;
                case 'B2CChargesPaidAccountAvailableFunds': $data['charges_paid_account_balance'] = (float) $value;
            }
        }

        DB::beginTransaction();

        $cashout = Cashout::where('conversation_id', $data['conversation_id'])->first();
        if (!$cashout) throw new CustomException('Transaction could not be found! Try again later.');
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
     * Validate Deposit
     * 
     */
    function validate_deposit(Request $request)
    {
        $result = $request->all();

        $user = User::where('username', $result['BillRefNumber'])->first();
        if (!$user) {
            // C2B00012 invalid account number error code
            $data['ResultCode'] = 'C2B00012';
            $data['ResultDesc'] = 'Rejected';
            return response()->json($data);
        }
        
        $deposit = Deposit::create([
            'owner_id' => $user->id,
            'trans_type' => $result['TransactionType'],
            'trans_id' => $result['TransID'],
            'trans_time' => $result['TransTime'],
            'trans_amount' => (float) $result['TransAmount'],
            'bill_ref_number' => $result['BillRefNumber'],
            'msisdn' => substr($result['MSISDN'], 0, 12),
        ]);

        return response()->json([
            'ResultCode' => '0',
            'ResultDesc' => 'Accepted',
            'data' => $result,
        ]);
    }

    /**
     * Store Deposit
     * 
     */
    function deposit(Request $request)
    {
        $result = $request->all();
        
        $data = [
            'trans_id' => $result['TransID'],
            'invoice_number' => $result['InvoiceNumber'],
            'org_account_balance' => (float) $result['OrgAccountBalance'],
            'thirdparty_trans_id' => $result['ThirdPartyTransID'],
            'msisdn' => substr($result['MSISDN'], 0, 12),
            'first_name' => $result['FirstName'],
            'middle_name' => $result['MiddleName'],
            'last_name' => $result['LastName'],
        ];

        DB::beginTransaction();

        $deposit = Deposit::where('trans_id', $data['trans_id'])->first();
        if (!$deposit) throw new CustomException('Transaction could not be found. Try again later.');
        $deposit->update($data);

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

    /**
     * Transaction Status
     */
    function transaction_status(Request $request)
    {
        $result = $this->transactionStatus([
            'transaction_id' => '',
            'party' => '',
            'identifier_type' => 1,
            'remark' => '',
            'occassion' => '',
        ]);

        return response()->json($result);
    }

    /**
     * Transaction Status Result
     */
    function status_result(Request $request)
    {
        $result = $request->Result;

        return response()->json($result);
    }
}
