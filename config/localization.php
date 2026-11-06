<?php

return [
    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
    'supported' => [
        'en' => ['name' => 'English', 'native_name' => 'English'],
        'ru' => ['name' => 'Russian', 'native_name' => 'Русский'],
    ],
];
