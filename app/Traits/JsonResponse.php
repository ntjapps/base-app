<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Response;

trait JsonResponse
{
    /**
     * JSON return wrapper for success
     */
    public function jsonSuccess(string $title, string $message, string $route = null, array $data = null): Response|HttpJsonResponse
    {
        if ($route !== null) {
            return response()->json([
                'status' => 'success',
                'title' => $title,
                'message' => $message,
                'redirect' => $route,
                'data' => $data,
            ]);
        } else {
            return response()->json([
                'status' => 'success',
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);
        }
    }

    /**
     * JSON return wrapper for failed
     */
    public function jsonFailed(string $title, string $message, string $route = null, array $data = null): Response|HttpJsonResponse
    {
        if ($route !== null) {
            return response()->json([
                'status' => 'failed',
                'redirect' => $route,
                'errors' => [
                    'message' => $message,
                ],
                'data' => $data,
            ], 422);
        } else {
            return response()->json([
                'status' => 'failed',
                'errors' => [
                    'message' => $message,
                ],
                'data' => $data,
            ], 422);
        }
    }
}
