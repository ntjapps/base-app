<?php

namespace App\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait WaApi
{
    /**
     * Send message wrapper
     */
    private function sendMessage($phone, $message, $file = null): void
    {
        try {
            if ($file != null) {
                $response = Http::asForm()->post('https://app.whacenter.com/api/send', [
                    'device_id' => config('waapi.device_id'),
                    'number' => $phone,
                    'message' => $message,
                    'file' => $file,
                ]);
            } else {
                $response = Http::asForm()->post('https://app.whacenter.com/api/send', [
                    'device_id' => config('waapi.device_id'),
                    'number' => $phone,
                    'message' => $message,
                ]);
            }

            Log::debug('Sending WA', ['deviceId' => config('waapi.device_id')]);

            /** Fallback to second number on failure */
            if ($response['status'] != true) {
                Log::warning('Fallback Message', ['phoneNum' => $phone]);
                $this->sendMessageFallback($phone, $message, $file);
            }
        } catch (ConnectionException $error) {
            Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
        }
    }

    /**
     * Send message wrapper
     */
    private function sendMessageFallback($phone, $message, $file = null): void
    {
        try {
            if ($file != null) {
                Http::asForm()->post('https://app.whacenter.com/api/send', [
                    'device_id' => config('waapi.device_id_2'),
                    'number' => $phone,
                    'message' => $message,
                    'file' => $file,
                ]);
            } else {
                Http::asForm()->post('https://app.whacenter.com/api/send', [
                    'device_id' => config('waapi.device_id_2'),
                    'number' => $phone,
                    'message' => $message,
                ]);
            }

            Log::debug('Sending WA', ['deviceId' => config('waapi.device_id_2')]);
        } catch (ConnectionException $error) {
            Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
        }
    }

    /**
     * Send message wrapper
     */
    private function sendScheduledMessage($phone, $message): void
    {
        try {
            Http::asForm()->post('https://app.whacenter.com/api/send', [
                'device_id' => config('waapi.device_id'),
                'number' => $phone,
                'message' => $message,
                'schedule' => now()->addMinutes(5)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
            ]);

            Log::debug('Sending WA', ['deviceId' => config('waapi.device_id')]);
        } catch (ConnectionException $error) {
            Log::channel('wamonitor')->error($error, ['app' => config('app.name')]);
        }
    }
}
