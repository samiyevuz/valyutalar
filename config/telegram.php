<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    */

    'bot_token' => env('TELEGRAM_BOT_TOKEN'),
    'bot_username' => env('TELEGRAM_BOT_USERNAME'),
    'webhook_url' => env('TELEGRAM_WEBHOOK_URL'),
    'secret_token' => env('TELEGRAM_SECRET_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Telegram API Base URL
    |--------------------------------------------------------------------------
    */

    'api_url' => 'https://api.telegram.org/bot',

    /*
    |--------------------------------------------------------------------------
    | Telegram IP Ranges (for webhook validation)
    |--------------------------------------------------------------------------
    */

    'allowed_ips' => [
        '149.154.160.0/20',
        '91.108.4.0/22',
        '91.108.8.0/22',
        '91.108.12.0/22',
        '91.108.16.0/22',
        '91.108.56.0/22',
        '185.76.151.0/24',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */

    'rate_limit' => [
        'max_requests' => 30,
        'per_minutes' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Commands
    |--------------------------------------------------------------------------
    */

    'commands' => [
        'start' => \App\Actions\Telegram\HandleStartAction::class,
        'help' => \App\Actions\Telegram\HandleHelpAction::class,
        'rate' => \App\Actions\Telegram\HandleRateAction::class,
        'convert' => \App\Actions\Telegram\HandleConvertAction::class,
        'history' => \App\Actions\Telegram\HandleHistoryAction::class,
        'banks' => \App\Actions\Telegram\HandleBanksAction::class,
        'alerts' => \App\Actions\Telegram\HandleAlertsAction::class,
        'profile' => \App\Actions\Telegram\HandleProfileAction::class,
        'favorites' => \App\Actions\Telegram\HandleFavoritesAction::class,
    ],
];

