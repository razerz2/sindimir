<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Default User
    |--------------------------------------------------------------------------
    |
    | These values are used by the database seeders to create the default
    | administrator account. Override in your ".env" file for each environment.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL', 'admin@sindimir.local'),
        'password' => env('ADMIN_PASSWORD', 'admin123'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tema (cores padrão)
    |--------------------------------------------------------------------------
    |
    | Valores padrão do tema. Podem ser sobrescritos no banco via Configuracao.
    |
    */

    'tema' => [
        'cor_primaria' => env('TEMA_COR_PRIMARIA', '#0f3d2e'),
        'cor_secundaria' => env('TEMA_COR_SECUNDARIA', '#ffffff'),
        'cor_fundo' => env('TEMA_COR_FUNDO', '#f6f7f9'),
        'cor_texto' => env('TEMA_COR_TEXTO', '#1f2937'),
        'cor_borda' => env('TEMA_COR_BORDA', '#e5e7eb'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Administrative Settings
    |--------------------------------------------------------------------------
    |
    | Configure thresholds used in admin dashboards.
    |
    */

    'dashboard' => [
        'limite_alerta_percentual' => (float) env('DASHBOARD_LIMITE_ALERTA_PERCENTUAL', 0.8),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduler
    |--------------------------------------------------------------------------
    |
    | Configurações dos jobs agendados.
    |
    */

    'scheduler' => [
        'lembrete_dias_antes' => (int) env('SCHEDULER_LEMBRETE_DIAS_ANTES', 2),
        'lembrete_horario' => env('SCHEDULER_LEMBRETE_HORARIO', '08:00'),
        'lembrete_email_assunto' => env('SCHEDULER_LEMBRETE_EMAIL_ASSUNTO', 'Lembrete de curso'),
        'lembrete_mensagem' => env(
            'SCHEDULER_LEMBRETE_MENSAGEM',
            'Lembrete: o curso {curso} inicia em {data_inicio} no local {local}.'
        ),
    ],

    'notification' => [
        'link_validade_minutos' => (int) env('NOTIFICATION_LINK_VALIDITY_MINUTES', 1440),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
