<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;

trait WaApi
{
  private function sendMessage($phone, $message)
  {
    try {
      $response = Http::asForm()->post('https://app.whacenter.com/api/send', [
        'device_id' => config('waapi.device_id'),
        'number' => $phone,
        'message' => $message,
      ]);

      Log::debug('Sending WA', ['deviceId' => config('waapi.device_id')]);

      /** Fallback to second number on failure */
      if ($response['status'] != true) {
        Log::warning('Fallback Message', ['phoneNum' => $phone]);
        $this->sendMessageFallback($phone,$message);
      }

      $this->updateWAStatus($response->json(), $phone);
    } catch (ConnectionException $error) {
      Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
    }
  }

  private function sendMessageFallback($phone, $message)
  {
    try {
      $response = Http::asForm()->post('https://app.whacenter.com/api/send', [
        'device_id' => config('waapi.device_id_2'),
        'number' => $phone,
        'message' => $message,
      ]);

      Log::debug('Sending WA', ['deviceId' => config('waapi.device_id_2')]);
  
      $this->updateWAStatus($response->json(), $phone);
    } catch (ConnectionException $error) {
      Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
    }
  }

  private function sendScheduledMessage($phone, $message)
  {
    try {
      $response = Http::asForm()->post('https://app.whacenter.com/api/send', [
        'device_id' => config('waapi.device_id'),
        'number' => $phone,
        'message' => $message,
        'schedule' => now()->addMinutes(5)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
      ]);

      Log::debug('Sending WA', ['deviceId' => config('waapi.device_id')]);
  
      $this->updateWAStatus($response->json(), $phone);
    } catch (ConnectionException $error) {
      Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
    }
  }

  private function updateWAStatus($response, $phone)
  {
    $user = User::find(User::where('phone', $phone)->first()->id);

    $resp_status = ($response['status'] == true) ? true : false ;

    $user->wa_status = $resp_status;
    $user->save();

    Log::debug('Update WA Status Phone: '.$phone, ['waStatus' => $resp_status]);

  }
}
