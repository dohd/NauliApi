<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Cashout;
use App\Models\Conductor;
use App\Models\Deposit;
use App\Models\User;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    /**
     * Update User Resource
     * 
     */
    public function update(Request $request, User $user) 
    {
        if ($user->rel_id) throw new CustomException('Unauthorized');

        $name = $request->name;
        $username = $request->username;
        $phone = $request->phone;
        $password = $request->password;

        if ($username) {
            $exists = User::where('id', '!=', $user->id)
                ->where('username', 'LIKE', "%{$username}%")
                ->exists();
            if ($exists) throw new CustomException('Username exists! Try a different name.', 409);
            $updated = $user->update(compact('username'));
        } elseif ($phone) {
            $exists = User::where('id', '!=', $user->id)
                ->where('phone', $phone)
                ->exists();
            if ($exists) throw new CustomException('Phone Number exists! Try a different number.', 409);
            $updated = $user->update(compact('phone'));
        } elseif ($name) {
            $updated = $user->update(compact('name'));
        } elseif ($password) {
            $updated = $user->update(compact('password'));
        }

        return response()->json(['message' => 'User updated successfully']);
    }

    /**
     * User Deposits
     */
    public function deposits(Request $request, $user) 
    {
        $deposits = Deposit::get([
            'id', 'owner_id', 'first_name', 'middle_name', 'last_name', 'msisdn', 
            'trans_amount', 'created_at'
        ]);

        return response()->json($deposits);
    }

    /**
     * User Conductors
     */
    public function conductors(Request $request, User $user) 
    {
        $conductors = Conductor::get(['id', 'username', 'phone', 'active']);

        return response()->json($conductors);
    }    

    /**
     * User Cashouts
     * 
     */
    public function cashouts(Request $request) {
        $cashouts = Cashout::get(['id', 'owner_id', 'trans_amount', 'created_at']);

        return response()->json($cashouts);
    }

    /**
     * User Wallet Balance
     * 
     */
    public function wallet_balance(Request $request)
    {
        $deposit = Deposit::latest()->first();
        $cashout = Cashout::latest()->first();

        if ($deposit && $cashout) {
            if (strtotime($cashout->created_at) > strtotime($deposit->created_at)) {
                $net_balance = ['amount' => $cashout->wallet_balance, 'fee' => $cashout->service_fee];
            } else {
                $net_balance = ['amount' => $deposit->wallet_balance, 'fee' => $deposit->service_fee];
            }
        } elseif ($deposit) {
            $net_balance = ['amount' => $deposit->wallet_balance, 'fee' => $deposit->service_fee];
        }

        return response()->json($net_balance);
    }
}
