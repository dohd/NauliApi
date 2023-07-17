<?php

if (!function_exists('spinner')) {
    function spinner() {
        return '<div class="d-flex justify-content-center"><div class="spinner-border text-primary ml-4" role="status"><span class="sr-only"></span></div></div>';
    }
}

if (!function_exists('inputClean')) {
    function inputClean($input=[])
    {
        $dates = ['date', 'start_date', 'end_date'];
        $totals = ['amount', 'total', 'grandtotal', 'subtotal', 'tax', 'rate', 'taxable', 'budget'];
        foreach ($input as $key => $value) {
            if (!is_array($value)) {
                if (in_array($key, $dates)) $input[$key] = databaseDate($value);
                elseif (in_array($key, $totals)) $input[$key] = numberClean($value);
                else $input[$key] = trim($value);
            }
        }
        return $input;
    }
}

if (!function_exists('fillArray')) {
    function fillArray($main=[], $params=[])
    {
        foreach ($params as $key => $value) {
            $main[$key] = $value;
        }
        return $main;
    }
}

if (!function_exists('fillArrayRecurse')) {
    function fillArrayRecurse($main=[], $params=[])
    {
        foreach ($main as $i => $row) {
            foreach ($params as $key => $value) {
                $main[$i][$key] = $value;
            }
        }
        return $main;
    }
}

if (!function_exists('explodeArray')) {
    function explodeArray($separator='', $input=[])
    {
        $input_mod = [];
        foreach ($input as $key => $value) {
            $input_mod[] = explode($separator, $value);
        }
        return $input_mod;
    }
}

if (!function_exists('numberClean')) {
    function numberClean($value='')
    {
        return floatval(str_replace(',', '', $value)); 
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($number=0, $deci=2)
    {
        return number_format($number, $deci);
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date='', $format='d-m-Y')
    {
        if (!$date) return date('d-m-Y');
        return date($format, strtotime($date));
    }
}

if (!function_exists('timeFormat')) {
    function timeFormat($time='', $format='h:i a')
    {
        if (!$time) return date("h:i:s a");
        return date($format, strtotime($time));
    }
}

if (!function_exists('databaseDate')) {
    function databaseDate($date='')
    {
        if (!$date) return date('Y-m-d');
        return date('Y-m-d', strtotime($date));
    }
}

if (!function_exists('databaseTimestamp')) {
    function databaseTimestamp($datetime='')
    {
        if (!$datetime) return date('Y-m-d H:i:s');
        return date('Y-m-d H:i:s', strtotime($datetime));
    }
}

if (!function_exists('databaseArray')) {
    function databaseArray($input=[])
    {
        $input_mod = [];
        foreach ($input as $key => $value) {
            foreach ($value as $j => $v) {
                $input_mod[$j][$key] = $v;
            }
        }
        return $input_mod;
    }
}

if (!function_exists('browserLog')) {
    function browserLog(...$messages)
    {
        foreach ($messages as $value) {
            echo '<script>console.log(' . json_encode($value) . ')</script>';
        }
    }
}

if (!function_exists('printLog')) {
    function printLog(...$messages)
    {
        foreach ($messages as $value) {
            error_log(print_r($value, 1));
        }
    }
}

if (!function_exists('errorHandler')) {
    function errorHandler($msg, $e)
    {
        if ($e instanceof Throwable) {
            $sys_error = $e->getMessage() . ' {user_id:'. auth()->user()->id . '} at ' . $e->getFile() . ':' . $e->getLine();
            \Illuminate\Support\Facades\Log::error($sys_error);
            printLog($sys_error);
        }
        
        return response()->json(['error' => 'Internal server error! Please try again later.'], 500);
    }
}

if (!function_exists('tidCode')) {
    function tidCode($prefix='', $num=0, $count=2)
    {
        if ($prefix) {
            $prefixInst = \Illuminate\Support\Facades\DB::table('prefixes')->where('name', $prefix)->first();
            if ($prefixInst) $prefix = "{$prefixInst->code}{$prefixInst->sep}";
        }
        return $prefix . sprintf('%0'.$count.'d', $num);
    }
}

if (!function_exists('processNetBalance')) {
    function processNetBalance($amount=0) 
    {
        $net_balance = ['amount' => 0, 'fee' => 0];
        if (!$amount) return $net_balance;

        // handle cashout limit
        $charge_config = \App\Models\ChargeConfig::first();
        $limit_amount = $charge_config->daily_free_cashout_limit_amount;
        $today = date('Y-m-d');
        $free_cashouts_count =  \App\Models\Transaction::whereHas('cashout')
        ->where('cashout', '<=', $limit_amount)->where('fee', 0)
        ->where('created_at', 'LIKE', "%{$today}%")
        ->count();
        if ($free_cashouts_count <= $charge_config->daily_free_cashout_limit) 
            return ['amount' => $amount, 'fee' => 0];

        // apply cashout fee
        $amount_inc = $amount;
        $fee_amount = round($amount_inc * $charge_config->pc_rate/100, 2);
        $amount_exc = $amount_inc - $fee_amount;
        $cashout_rates = \App\Models\CashoutRate::get();
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
                $net_balance = ['amount' => $net_amount, 'fee' => $fee_amount];
                break;
            } 
        }
        return $net_balance;
    }
}
