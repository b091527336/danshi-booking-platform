<?php

return [
    'tablesit' => [
        'base_url' => env('TABLESIT_BASE_URL', 'https://www.tablesit.co/api/v1'),
        'api_keys' => json_decode(env('TABLESIT_API_KEYS_JSON', '{}'), true) ?: [],
        'timeout' => (int) env('TABLESIT_TIMEOUT', 15),
        'max_pages' => (int) env('TABLESIT_MAX_PAGES', 100),
    ],
];
