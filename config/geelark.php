<?php

return [

    'base_url' => env('GEELARK_BASE_URL', 'https://openapi.geelark.com'),

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    | mode: "token" (Bearer) or "key" (appId + sign)
    */
    'auth_mode' => env('GEELARK_AUTH_MODE', 'token'),

    'api_token' => env('GEELARK_API_TOKEN'),

    'app_id' => env('GEELARK_APP_ID'),

    'api_key' => env('GEELARK_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Demo mode
    |--------------------------------------------------------------------------
    | When true (or when credentials are missing), the API client returns
    | simulated responses so the UI can be explored offline.
    */
    'demo_mode' => filter_var(env('GEELARK_DEMO_MODE', true), FILTER_VALIDATE_BOOLEAN),

    'timeout' => (int) env('GEELARK_TIMEOUT', 30),

];
