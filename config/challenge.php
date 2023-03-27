<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Challenge
    |--------------------------------------------------------------------------
    | URL for verification and site secret for challenge, wheter it's Google or
    | any other challenge provider.
    */

    'url' => env('APP_CHALLENGE_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    'site_secret' => env('APP_CHALLENGE_SITE_SECRET'),

    'bypass' => env('APP_CHALLENGE_BYPASS', false),

    'mobile' => env('APP_CHALLENGE_MOBILE', null),

    'platforms' => [
        'android',
        'ios',
    ],
];
