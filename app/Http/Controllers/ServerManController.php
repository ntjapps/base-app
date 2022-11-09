<?php

namespace App\Http\Controllers;

use App\Logger\Models\ServerLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServerManController extends Controller
{    
    /**
     * GET request to view server logs layouts
     */
    public function serverLogs()
    {
      Log::info('User '.Auth::user()?->name.' open server log', ['user_id' => Auth::id()]);

      return view('super-pg.serverlog');
    }

    /**
     * POST request to get server Logs from tables
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getServerLogs(Request $request)
    {
      Log::info('User '.Auth::user()?->name.' get server log', ['user_id' => Auth::id()]);

      $data = ServerLog::all();

      return response()->json($data);
    }
}
