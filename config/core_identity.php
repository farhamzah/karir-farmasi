<?php

return [
    'driver' => env('CORE_IDENTITY_DRIVER', 'unavailable'),
    'app_code' => 'karir-farmasi',
    'recovery_url' => env('CORE_PASSWORD_RECOVERY_URL'),
    'http' => [
        'enabled' => env('CORE_IDENTITY_HTTP_ENABLED', false),
        'verify_url' => env('CORE_IDENTITY_VERIFY_URL'),
        'directory_base_url' => env('CORE_DIRECTORY_BASE_URL'),
        'alumni_base_url' => env('CORE_ALUMNI_BASE_URL'),
        'client_id' => env('CORE_IDENTITY_CLIENT_ID'),
        'client_secret' => env('CORE_IDENTITY_CLIENT_SECRET'),
        'connect_timeout_seconds' => 2,
        'timeout_seconds' => 5,
    ],
    'allowed_program_ids' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORE_IDENTITY_ALLOWED_PROGRAM_IDS', '')),
    ))),
];
