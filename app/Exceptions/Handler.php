<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
        ValidationException::class,
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
        // custom error handling
        $this->reportable(function (CustomException $e) {
            return false;
        });

        // laravel validation error handling 
        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->validator->errors()->getMessages(),
            ], 422);
        });

        // default error handling
        $this->reportable(function (Throwable $e) {
            $sys_message = $e->getMessage() . ' {user_id:'. auth()->user()->id . '} at ' . $e->getFile() . ':' . $e->getLine();
            printLog($sys_message);
            Log::error($sys_message);
        });
        $this->renderable(function (Throwable $e) {
            return response()->json(['message' => 'Oops! Something went wrong. Please try again later'], 500);
        });
    }
}
