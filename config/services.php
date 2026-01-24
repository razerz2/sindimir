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

    'whatsapp' => [
        'zapi' => [
            'enabled' => env('WHATSAPP_ZAPI_ENABLED', false),
            'base_url' => env('WHATSAPP_ZAPI_BASE_URL'),
            'token' => env('WHATSAPP_ZAPI_TOKEN'),
            'instance' => env('WHATSAPP_ZAPI_INSTANCE'),
        ],
        'meta' => [
            'enabled' => env('WHATSAPP_META_ENABLED', false),
            'base_url' => env('WHATSAPP_META_BASE_URL', 'https://graph.facebook.com/v21.0'),
            'token' => env('WHATSAPP_META_TOKEN'),
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
        ],
    ],

];
