<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | Your letmesend.email API key. You can set this in your .env file:
    | LETMESENDEMAIL_API_KEY=lms_live_...
    |
    */

    'api_key' => env('LETMESENDEMAIL_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The API base URL. Override only for local development or self-hosted
    | instances.
    |
    */

    'base_url' => env('LETMESENDEMAIL_BASE_URL', 'https://letmesend.email/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for API responses.
    |
    */

    'timeout' => (int) env('LETMESENDEMAIL_TIMEOUT', 30),

];
