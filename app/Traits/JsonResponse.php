<?php

namespace App\Traits;

trait JsonResponse
{
  /**
   * JSON return wrapper for success
   */
  public function jsonSuccess(string $title, string $message, string $route=null)
  {
    if ($route !== null) {
      return response()->json([
        'title' => $title,
        'message' => $message,
        'redirect' => $route,
      ]);
    } else {
      return response()->json([
        'title' => $title,
        'message' => $message,
      ]);
    }
  }

  /**
   * JSON return wrapper for failed
   */
  public function jsonFailed(string $title='', string $message, string $route=null)
  {
    if ($route !== null) {
      return response()->json([
        'redirect' => $route,
        'errors' => [
          'message' => $message,
        ],
      ], 422);
    } else {
      return response()->json([
        'errors' => [
          'message' => $message,
        ],
      ], 422);
    }
  }
}