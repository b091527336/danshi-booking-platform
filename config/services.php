<?php

return [
    'tablesit' => [
        'base_url' => env('TABLESIT_BASE_URL'),
        'api_key' => env('TABLESIT_API_KEY'),
        'partner_id' => env('TABLESIT_PARTNER_ID'),
        'auth_type' => env('TABLESIT_AUTH_TYPE', 'bearer'),
        'auth_header' => env('TABLESIT_AUTH_HEADER', 'X-API-Key'),
        'bookings_path' => env('TABLESIT_BOOKINGS_PATH', '/bookings'),
        'organization_parameter' => env('TABLESIT_ORGANIZATION_PARAMETER', 'organization_id'),
        'updated_after_parameter' => env('TABLESIT_UPDATED_AFTER_PARAMETER', 'updated_after'),
        'page_parameter' => env('TABLESIT_PAGE_PARAMETER', 'page'),
        'timeout' => (int) env('TABLESIT_TIMEOUT', 15),
        'max_pages' => (int) env('TABLESIT_MAX_PAGES', 100),
    ],
];
