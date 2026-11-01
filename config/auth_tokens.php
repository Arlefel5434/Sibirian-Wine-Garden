<?php

return [
    'refresh' => [
        'cookie' => env('REFRESH_TOKEN_COOKIE', 'wine_garden_refresh'),
        'ttl' => (int) env('REFRESH_TOKEN_TTL', 20160),
        'path' => env('REFRESH_TOKEN_PATH', '/api/auth'),
        'domain' => env('REFRESH_TOKEN_DOMAIN'),
        'secure' => (bool) env('REFRESH_TOKEN_SECURE_COOKIE', false),
        'same_site' => env('REFRESH_TOKEN_SAME_SITE', 'strict'),
    ],
];
