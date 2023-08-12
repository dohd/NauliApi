<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // report custom error
        $this->reportable(function (CustomException $e) {
            return false;
        });

        // render http request error 
        $this->renderable(function (RequestException $e) {
            return response()->json(['message' => 'Network error! Please try again later'], 400);
        });

        // render authentication error
        $this->renderable(function (AuthenticationException $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        });

        // render laravel validation 
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->validator->errors()->getMessages(),
            ], 422);
        });

        // report default error
        $this->reportable(function (Throwable $e) {
            $msg = $e->getMessage() . ' {user_id:'. @auth()->user()->id . '} at ' . $e->getFile() . ':' . $e->getLine();
            printLog($msg);
            Log::error($msg);
        });
        // render default error
        $this->renderable(function (Throwable $e) {
            return response()->json(['message' => 'Something went wrong! Please try again later'], 500);
        });
    }
}
