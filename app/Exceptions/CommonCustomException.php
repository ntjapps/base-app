<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class CommonCustomException extends Exception
{
    /**
     * Modify parent construct
     */
    public function __construct($message = '', $code = 422, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     * FALSE | NULL = report to log
     */
    public function report(): ?bool
    {
        return false;
    }

    /**
     * Get the exception's context.
     */
    public function context(): array
    {
        return [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
            'trace' => $this->getTraceAsString(),
            'previous' => $this->getPrevious()?->getMessage(),
            'previous_trace' => $this->getPrevious()?->getTraceAsString(),
        ];
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function render($request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'errors' => [
                'message' => $this->getMessage(),
            ],
        ], $this->getCode());
    }
}
