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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'whatsapp' => [
        'provider_registry' => [
            'meta' => \App\Services\WhatsApp\Providers\MetaWhatsAppProvider::class,
            'zapi' => \App\Services\WhatsApp\Providers\ZApiWhatsAppProvider::class,
            'waha' => \App\Services\WhatsApp\Providers\WahaWhatsAppProvider::class,
            'evolution' => \App\Services\WhatsApp\Providers\EvolutionWhatsAppProvider::class,
        ],
        'future_providers' => [],
        'zapi' => [
            'enabled' => env('WHATSAPP_ZAPI_ENABLED', false),
            'base_url' => env('WHATSAPP_ZAPI_BASE_URL'),
            'token' => env('WHATSAPP_ZAPI_TOKEN'),
            'client_token' => env('WHATSAPP_ZAPI_CLIENT_TOKEN'),
            'instance' => env('WHATSAPP_ZAPI_INSTANCE'),
            'verify_ssl' => env('WHATSAPP_ZAPI_VERIFY_SSL', true),
        ],
        'meta' => [
            'enabled' => env('WHATSAPP_META_ENABLED', false),
            'base_url' => env('WHATSAPP_META_BASE_URL', 'https://graph.facebook.com/v21.0'),
            'token' => env('WHATSAPP_META_TOKEN'),
            'phone_number_id' => env('WHATSAPP_META_PHONE_NUMBER_ID'),
            'verify_ssl' => env('WHATSAPP_META_VERIFY_SSL', true),
        ],
        'waha' => [
            'enabled' => env('WHATSAPP_WAHA_ENABLED', false),
            'status_enabled' => env('WHATSAPP_WAHA_STATUS_ENABLED', true),
            'base_url' => env('WHATSAPP_WAHA_BASE_URL'),
            'api_key' => env('WHATSAPP_WAHA_API_KEY'),
            'api_key_header' => env('WHATSAPP_WAHA_API_KEY_HEADER', 'X-Api-Key'),
            'session' => env('WHATSAPP_WAHA_SESSION', 'default'),
            'verify_ssl' => env('WHATSAPP_WAHA_VERIFY_SSL', true),
        ],
        'evolution' => [
            'enabled' => env('WHATSAPP_EVOLUTION_ENABLED', false),
            'status_enabled' => env('WHATSAPP_EVOLUTION_STATUS_ENABLED', true),
            'base_url' => env('WHATSAPP_EVOLUTION_BASE_URL'),
            'apikey' => env('WHATSAPP_EVOLUTION_APIKEY'),
            'instance' => env('WHATSAPP_EVOLUTION_INSTANCE'),
            'verify_ssl' => env('WHATSAPP_EVOLUTION_VERIFY_SSL', true),
        ],
    ],

];
