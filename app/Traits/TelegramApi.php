<?php

namespace App\Traits;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

trait TelegramApi
{
    /**
     * Private function for checking if the Telegram API is available.
     */
    private function isTelegramApiAvailable(): bool
    {
        try {
            $response = Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/getMe');

            return $response['ok'] ?? false;
        } catch (ConnectionException $e) {
            return false;
        }
    }

    /**
     * Private function for sending message to Telegram.
     */
    private function sendTelegramMessage(string $message, ?string $chatId = null): bool
    {
        try {
            $response = Http::asForm()->post(config('telegram.endpoint').config('telegram.token').'/sendMessage', [
                'chat_id' => $chatId ?? config('telegram.group_id'),
                'text' => substr($message, 0, 4096),
            ]);

            return $response['ok'] ?? false;
        } catch (ConnectionException $e) {
            return false;
        }
    }
}
