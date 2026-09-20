<?php

$tableSitBaseUrl = env('TABLESIT_BASE_URL', 'https://www.tablesit.co/api/v1');

return [
    'tablesit' => [
        'base_url' => $tableSitBaseUrl,
        'partner_base_url' => env(
            'TABLESIT_PARTNER_BASE_URL',
            str_replace('/api/v1', '/api/partner/v1', $tableSitBaseUrl),
        ),
        'partner_api_key' => env('TABLESIT_PARTNER_API_KEY'),
        'owner_email' => env('TABLESIT_OWNER_EMAIL', env('DBP_ADMIN_EMAIL')),
        'api_keys' => json_decode(env('TABLESIT_API_KEYS_JSON', '{}'), true) ?: [],
        'timeout' => (int) env('TABLESIT_TIMEOUT', 15),
        'max_pages' => (int) env('TABLESIT_MAX_PAGES', 100),
    ],
];
