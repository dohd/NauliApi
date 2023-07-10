<?php

namespace App\Http\Controllers;

use App\Models\CashoutRate;
use App\Models\ChargeConfig;
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
        if (!$user->id || $user->rel_id) {
            $error_status = 401;
            trigger_error('Unauthorized');
        }

        $name = $request->name;
        $username = $request->username;
        $phone = $request->phone;
        $password = $request->password;

        try {
            if ($username) {
                $exists = User::where('id', '!=', $user->id)
                    ->where('username', 'LIKE', "%{$username}%")->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Username exists! Try a different name.');
                }
                $updated = $user->update(compact('username'));
            } elseif ($phone) {
                $exists = User::where('id', '!=', $user->id)
                    ->where('phone', $phone)->exists();
                if ($exists) {
                    $error_status = 409;
                    trigger_error('Phone Number exists! Try a different number.');
                }
                $updated = $user->update(compact('phone'));
            } elseif ($name) {
                $updated = $user->update(compact('name'));
            } elseif ($password) {
                $updated = $user->update(compact('password'));
            }

            return response()->json(['message' => 'User updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], @$error_status ?: 500);
        }
    }

    /**
     * User Account Balance
     */
    public function account_balance(Request $request, User $user)
    {
        $balance = [
            'amount' => number_format(800, 2), 
            'currency' => 'Ksh. '
        ];
        $is_charged_cashout = false;

        $charge_config = ChargeConfig::first();
        // count free cashouts where amount <= daily_free_cashout_limit_amount
        $free_cashouts = 5; 
        if ($free_cashouts == $charge_config->daily_free_cashout_limit)
            $is_charged_cashout = true;

        if ($is_charged_cashout) {
            // apply cashout charges
            $amount_inc = floatval(str_replace(',', '', $balance['amount']));
            $fee_amount = round($amount_inc * $charge_config->pc_rate/100, 2);
            $amount_exc = $amount_inc - $fee_amount;

            $cashout_rates = CashoutRate::get();
            foreach ($cashout_rates as $band) {
                if ($amount_inc >= $band->lower_class && $amount_inc <= $band->upper_class) {
                    if (round($fee_amount) <= round($band->rate)) {
                        $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                        $net_amount = $amount_exc + $retainer;
                        $fee_amount -= $retainer;
                    } elseif (round($fee_amount) > round($band->rate)) {
                        // address sharp variance in rate bwetween 200 and 500
                        if ($amount_inc > 200 && $amount_inc < 500) {
                            $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                            $net_amount = $amount_exc + $retainer;
                            $fee_amount -= $retainer;
                        } else {
                            // apply rates from table
                            $fee_amount -= $band->rate;
                            $retainer = floor($fee_amount * $charge_config->pc_retainer/100);
                            $net_amount = $amount_exc + $band->rate + $retainer;
                            $fee_amount -= $retainer;
                        }
                    }
                    $net_amount = floor($net_amount);
                    $fee_amount = ceil($fee_amount);
                    $balance = array_replace($balance, [
                        'net_amount' => number_format($net_amount, 2),
                        'fee_amount' => number_format($fee_amount, 2),
                    ]);
                    break;
                } 
            }
        }

        // trigger B2C transaction in daraja api

        return response()->json($balance);
    }

    /**
     * User Deposits
     */
    public function user_deposits(Request $request, $user) 
    {
        $deposits = [
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
        $deposits = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'name' => $v,
            'phone' => '07' . rand(10000000, 99999999),
            'amount' => number_format(100, 2),
            'currency' => 'Ksh.',
            'date' => date('d-m-Y'),
            'time' => date('g:i a'),
        ], $deposits);

        // trigger B2C transaction in daraja api

        return response()->json($deposits);
    }

    /**
     * User Conductors
     */
    public function user_conductors(Request $request, User $user) 
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
     * User Withdrawals
     */
    public function user_withdrawals(Request $request, $user) {
        $withdrawals = array_map(fn($v) => [
            'id' => rand(1000,9999),
            'amount' => number_format(100, 2),
            'currency' => 'Ksh.',
            'date' => date('d-m-Y'),
            'time' => date('g:i a'),
        ], range(1, 20));

        return response()->json($withdrawals);
    }

    /**
     * Withdrawal Pre-confirmation OTP code
     * 
     */
    public function withdrawal_otp(Request $request, User $user) {
        try {
            if (!$user) trigger_error('Unauthorized!');

            // otp expiry 120 seconds
            $user->update([
                'withdraw_otp' => rand(100000, 999999), 
                'withdraw_otp_exp' => date('Y-m-d H:i:s', strtotime(date("Y-m-d H:i:s")) + 120),
            ]);
            
            // send otp using sms service

            return response()->json(['message' => 'OTP Generated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 401);
        }
    }

    /**
     * Withdrawal Confirmation
     */
    public function confirm_withdrawal(Request $request, User $user) {
        $request->validate(['amount' => 'required', 'otp' => 'required']);
        $input = $request->only('user_id', 'amount', 'otp');

        try {
            if (!$user) trigger_error('Unauthorized!');

            // verify otp expiry
            $exp_diff = strtotime(date('Y-m-d H:i:s')) - strtotime($user->withdraw_otp_exp);
            if ($exp_diff > 0) trigger_error('Expired OTP code.');

            // trigger B2C transaction in daraja api

            return response()->json(['message' => 'Withdrawal transaction processed successfully']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 401);
        }
    }
}
