<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Currency API Configuration
    |--------------------------------------------------------------------------
    */

    'api_provider' => env('CURRENCY_API_PROVIDER', 'cbu'),

    'providers' => [
        'cbu' => [
            'base_url' => 'https://cbu.uz/ru/arkhiv-kursov-valyut/json/',
        ],
        'exchangerate' => [
            'base_url' => 'https://api.exchangerate.host',
            'api_key' => env('EXCHANGERATE_API_KEY'),
        ],
        'openexchangerates' => [
            'base_url' => 'https://openexchangerates.org/api',
            'api_key' => env('OPENEXCHANGERATES_API_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    */

    'supported' => ['USD', 'EUR', 'RUB', 'UZS', 'GBP', 'CNY', 'JPY', 'CHF', 'KZT'],

    'base_currency' => 'UZS',

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'ttl' => 1800, // 30 minutes
        'prefix' => 'currency_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Sources for UZS
    |--------------------------------------------------------------------------
    */

    'banks' => [
        'uzum' => [
            'name' => 'Uzum Bank',
            'code' => 'uzum',
        ],
        'ipak_yuli' => [
            'name' => 'Ipak Yuli Bank',
            'code' => 'ipak_yuli',
        ],
        'kapitalbank' => [
            'name' => 'Kapitalbank',
            'code' => 'kapitalbank',
        ],
        'trastbank' => [
            'name' => 'Trastbank',
            'code' => 'trastbank',
        ],
        'hamkorbank' => [
            'name' => 'Hamkorbank',
            'code' => 'hamkorbank',
        ],
        'tbc' => [
            'name' => 'TBC Bank',
            'code' => 'tbc',
        ],
        'nbu' => [
            'name' => 'National Bank',
            'code' => 'nbu',
        ],
        'asakabank' => [
            'name' => 'Asakabank',
            'code' => 'asakabank',
        ],
    ],
];

