<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Cashout;
use App\Models\CashoutRate;
use App\Models\ChargeConfig;
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
        // $conductors = $user->conductors;

        $users = [
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
            "Fuxi Isak",
            "Lola Azra",
            "Sujata Devyn",
            "Ida Roman",
            "Sherry Argider",
        ];
        $users = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'username' => $v,
            'phone' => '07' . rand(10000000, 99999999),
            'active' => rand(0,1),
        ], $users);

        return response()->json($users);
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
    public function wallet_balance(Request $request, User $user)
    {
        // limit free cashouts where amount <= daily_free_cashout_limit_amount
        $free_cashouts = 5; 
        $charge_config = ChargeConfig::first();
        if ($free_cashouts == $charge_config->daily_free_cashout_limit)
            $is_charged_cashout = true;

        $amount = Deposit::sum('trans_amount');
        $balance = compact('amount');
        if (@$is_charged_cashout)
            $balance = $this->processNetBalance($balance['amount'],  $charge_config);

        return response()->json($balance);
    }

    // process net balance helper
    public function processNetBalance($amount=0, $charge_config) 
    {
        $cashout_rates = CashoutRate::get();

        $amount_inc = $amount;
        $fee_amount = round($amount_inc * $charge_config->pc_rate/100, 2);
        $amount_exc = $amount_inc - $fee_amount;

        $net_balance = [];
        foreach ($cashout_rates as $bracket) {
            if ($amount_inc >= $bracket->lower_limit && $amount_inc <= $bracket->upper_limit) {
                if (round($fee_amount) <= round($bracket->rate)) {
                    $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                    $net_amount = $amount_exc + $retainer;
                    $fee_amount -= $retainer;
                } elseif (round($fee_amount) > round($bracket->rate)) {
                    // address sharp variance in rate bwetween 200 and 500
                    if ($amount_inc > 200 && $amount_inc < 500) {
                        $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                        $net_amount = $amount_exc + $retainer;
                        $fee_amount -= $retainer;
                    } else {
                        // apply rates from table
                        $fee_amount -= $bracket->rate;
                        $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                        $net_amount = $amount_exc + $bracket->rate + $retainer;
                        $fee_amount -= $retainer;
                    }
                }
                $net_amount = floor($net_amount);
                $fee_amount = ceil($fee_amount);
                $net_balance = [
                    'amount' => $net_amount,
                    'fee' => $fee_amount,
                ];
                break;
            } 
        }

        return $net_balance;
    }
}
