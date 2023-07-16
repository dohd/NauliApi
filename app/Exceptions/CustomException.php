<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    public function __construct($message='', $code=400) {
        $this->message = $message;
        $this->code = $code;
    }

    public function render() 
    {
        return response()->json([
            'message' => $this->message,
            'errors' => [],
        ], $this->code);
    }
}
