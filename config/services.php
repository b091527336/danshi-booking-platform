<?php

return [
    'tablesit' => [
        'base_url' => env('TABLESIT_BASE_URL'),
        'api_key' => env('TABLESIT_API_KEY'),
        'partner_id' => env('TABLESIT_PARTNER_ID'),
        'timeout' => (int) env('TABLESIT_TIMEOUT', 15),
    ],
];
