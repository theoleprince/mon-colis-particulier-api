<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Client app codes
    |--------------------------------------------------------------------------
    | Every mobile call carries a `client-app-code` header (legacy contract of
    | the former Symfony API). Comma-separated list of accepted codes. When the
    | list is empty the check is disabled (local dev / tests).
    */
    'client_app_codes' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('MONCOLIS_CLIENT_APP_CODES', ''))
    ))),

    /*
    | Country dialing code applied to local phone numbers ("677897012" -> "+237677897012").
    */
    'default_country_code' => env('MONCOLIS_DEFAULT_COUNTRY_CODE', '237'),

    'auth' => [
        // Lifetime of a mobile access token, in days.
        'token_ttl_days' => (int) env('MONCOLIS_TOKEN_TTL_DAYS', 30),
        // Login attempts allowed per minute for one identifier + IP.
        'login_attempts_per_minute' => (int) env('MONCOLIS_LOGIN_ATTEMPTS', 5),
    ],

    'otp' => [
        'length' => 6,
        'ttl_seconds' => (int) env('MONCOLIS_OTP_TTL', 300),
        'max_attempts' => 5,
        // Minimum delay between two codes sent to the same destination.
        'resend_cooldown_seconds' => (int) env('MONCOLIS_OTP_COOLDOWN', 60),
        // Exposes the code in API responses — local environment only, never in production.
        'expose_in_response' => (bool) env('MONCOLIS_OTP_EXPOSE', false),
    ],

    /*
    | API documentation (/docs/api, /docs/swagger) — always open in local env,
    | elsewhere only when this flag is true (e.g. staging for the mobile team).
    */
    'docs_public' => (bool) env('API_DOCS_PUBLIC', false),

    'profile' => [
        'avatar_disk' => env('MONCOLIS_AVATAR_DISK', 'public'),
        'avatar_max_kb' => 5120,
        'max_saved_places' => 20,
    ],

];
