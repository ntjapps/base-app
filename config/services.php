<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'laravelpassport' => [
        'client_id' => env('LARAVELPASSPORT_CLIENT_ID'),
        'client_secret' => env('LARAVELPASSPORT_CLIENT_SECRET'),
        'redirect' => env('LARAVELPASSPORT_REDIRECT_URI'),
        'host' => env('LARAVELPASSPORT_HOST'),
    ],

    'rabbitmq' => [
        'enabled' => (bool) env('RABBITMQ_ENABLED', false),
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'queueuser'),
        'password' => env('RABBITMQ_PASSWORD', 'queuepass'),
        'vhost' => env('RABBITMQ_VHOST', 'queuevhost'),
        'timeout' => env('RABBITMQ_TIMEOUT_SECONDS', 60),
    ],

    'waha' => [
        'base_url' => env('WAHA_API_BASE_URL', 'http://localhost:3000'),
        'session' => env('WAHA_API_SESSION', 'default'),
    ],

    'whatsapp' => [
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'your_default_verify_token'),
        'endpoint' => env('WHATSAPP_API_ENDPOINT', 'https://graph.facebook.com/v23.0/'),
        'business_id' => env('WHATSAPP_BUSINESS_ID'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'veriId' => env('WHATSAPP_VERIFICATION_ID', 'your_default_verification_id'),
    ],
];
