<?php

namespace App\Traits;

trait JsonResponse
{
  public function jsonSuccess($title,$message,$route=null)
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

  public function jsonFailed($title,$message,$route=null)
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