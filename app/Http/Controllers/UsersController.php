<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Cashout;
use App\Models\Conductor;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use DarajaApi;

    /**
     * Show Auth User
     */
    public function show(Request $request, User $user) 
    {
        $user = auth()->user();
        return response()->json($user);
    }

    /**
     * Update User Resource
     * 
     */
    public function update(Request $request, User $user) 
    {
        if ($user->rel_id) throw new CustomException('Unauthorized', 401);

        $name = $request->name;
        $username = $request->username;
        $phone = $request->phone;
        $current_password = $request->current_password;
        $password = $request->password;

        if ($username) {
            $exists = User::where('id', '!=', $user->id)->where('username', 'LIKE', "%{$username}%")
                ->exists();
            if ($exists) throw new CustomException('Username exists! Try a different name.', 409);
            $updated = $user->update(compact('username'));
        }
        if ($phone) {
            $exists = User::where('id', '!=', $user->id)->where('phone', $phone)
                ->exists();
            if ($exists) throw new CustomException('Phone Number exists! Try a different number.', 409);
            $updated = $user->update(compact('phone'));
        } 
        if ($name) {
            $updated = $user->update(compact('name'));
        } 
        if ($password) {
            if (!password_verify($current_password, $user->password))
                throw new CustomException('Invalid password', 401);
            $updated = $user->update(compact('password'));
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * User Deposits
     */
    public function deposits(Request $request, $user) 
    {
        if ($request->reload) $this->update_deposit($request, $user);
            
        $deposits = Deposit::get([
            'id', 'owner_id', 'first_name', 'middle_name', 'last_name', 'msisdn', 
            'trans_amount', 'created_at'
        ]);

        return response()->json($deposits);
    }

    /**
     * Update Deposit Transaction
     */
    public function update_deposit(Request $request, $user) 
    {
        $deposit = Deposit::latest()->first();
        printLog($deposit->toArray());
        // validate
        // check transaction status
        // update deposit transaction
        return true;
    }

    /**
     * User Conductors
     */
    public function conductors(Request $request, User $user) 
    {
        $conductors = Conductor::get(['id', 'name', 'username', 'phone', 'active']);

        return response()->json($conductors);
    } 
    
    /**
     * User Cashouts
     * 
     */
    public function cashouts(Request $request) 
    {
        $cashouts = Cashout::get(['id', 'owner_id', 'trans_amount', 'created_at']);

        return response()->json($cashouts);
    }

    /**
     * User Wallet Balance
     * 
     */
    public function wallet_balance(Request $request)
    {        
        $net_balance = 0;
        $transaction = Transaction::latest()->first();
        if ($transaction && $transaction->net_balance > 0)
            $net_balance = $transaction->net_balance;

        return response()->json(compact('net_balance'));
    }
}
