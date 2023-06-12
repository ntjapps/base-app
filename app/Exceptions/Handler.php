<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Exceptions\OAuthServerException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (OAuthServerException $e, Request $request) {
            Log::warning('OAuthServerException intercepted', ['exception' => 'OAuthServerException', 'message' => $e->getMessage(), 'code' => $e->getCode(), 'trace' => $e->getTraceAsString(), 'requestIp' => $request->ip()]);
            return response()->json([
                'error' => $e->getErrorType(),
                'message' => $e->getMessage(),
            ], $e->getHttpStatusCode());
        });
    }
}
