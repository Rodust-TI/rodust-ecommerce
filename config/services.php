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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'bling' => [
        // API v3 (OAuth2)
        'client_id' => env('BLING_CLIENT_ID'),
        'client_secret' => env('BLING_CLIENT_SECRET'),
        'base_url' => env('BLING_BASE_URL', 'https://api.bling.com.br/Api/v3'),
        'default_warehouse_id' => env('BLING_DEFAULT_WAREHOUSE_ID', 1),
        
        // Redirect para OAuth (configure no painel Bling)
        'redirect_uri' => env('BLING_REDIRECT_URI', env('APP_URL') . '/bling/callback'),
    ],

];
