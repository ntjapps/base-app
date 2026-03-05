<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class CommonCustomException extends Exception
{
    protected array $meta = [];

    /**
     * Modify parent construct
     */
    public function __construct($message = '', $code = 422, ?Throwable $previous = null, array $meta = [])
    {
        parent::__construct($message, $code, $previous);
        $this->meta = $meta;
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
            'meta' => $this->meta,
        ];
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function render($request): JsonResponse
    {
        $payload = [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'errors' => [
                'message' => $this->getMessage(),
            ],
        ];

        if (! empty($this->meta)) {
            $payload['meta'] = $this->meta;
        }

        // Ensure we return a valid HTTP status code (Symfony will throw if it's invalid).
        $httpStatus = (int) $this->getCode();
        if ($httpStatus < 100 || $httpStatus > 599) {
            // Use 422 Unprocessable Entity as a safe default for application errors
            $httpStatus = 422;
        }

        // Keep payload code consistent with returned HTTP status
        $payload['code'] = $httpStatus;

        return response()->json($payload, $httpStatus);
    }
}
