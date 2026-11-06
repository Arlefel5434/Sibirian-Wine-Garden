<?php

return [
    'enabled' => env('CONTENT_CACHE_ENABLED', true),
    'ttl' => (int) env('CONTENT_CACHE_TTL', 1800),
    'lock_seconds' => (int) env('CONTENT_CACHE_LOCK_SECONDS', 10),
    'lock_wait_seconds' => (int) env('CONTENT_CACHE_LOCK_WAIT_SECONDS', 2),
    'schema' => 1,
];
