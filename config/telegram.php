<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Telegram Chat Token
    |--------------------------------------------------------------------------
    | This is the token of your Telegram chat.
    | Use group id for group notification.
    |
    */

    'endpoint' => env('TELEGRAM_ENDPOINT', 'https://api.telegram.org/bot'),

    'token' => env('TELEGRAM_TOKEN'),

    'group_id' => env('TELEGRAM_GROUP_ID'),

];
