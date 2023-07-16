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
