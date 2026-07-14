<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | This key authenticates requests sent to the letmesend.email API. When it
    | is not set here, the package also checks services.letmesendemail.key.
    |
    */

    'api_key' => env('LETMESENDEMAIL_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | This is the root URL used for all API requests. The default points to the
    | production v1 API; override it only for a compatible endpoint.
    |
    */

    'base_url' => env('LETMESENDEMAIL_BASE_URL', 'https://letmesend.email/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | This value controls how many seconds an API request may run before the
    | underlying HTTP client treats it as timed out.
    |
    */

    'timeout' => (int) env('LETMESENDEMAIL_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts
    |--------------------------------------------------------------------------
    |
    | This is the number of additional attempts allowed for eligible transient
    | failures. Write requests are retried only when they are safe to retry.
    |
    */

    'retries' => (int) env('LETMESENDEMAIL_RETRIES', 0),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | These options control the package-provided webhook endpoint. Enable the
    | route explicitly, choose its URI and middleware, provide the signing
    | secret, and set the accepted timestamp tolerance in seconds.
    |
    */

    'webhooks' => [
        // Register the package-provided webhook route when enabled.
        'enabled' => env('LETMESENDEMAIL_WEBHOOKS_ENABLED', false),

        // The URI where letmesend.email will deliver webhook requests.
        'path' => env('LETMESENDEMAIL_WEBHOOK_PATH', '/webhooks/letmesendemail'),

        // Middleware applied to the route, including signature verification.
        'middleware' => ['letmesendemail.webhook'],

        // The secret used to verify webhook signatures; never expose this value.
        'signing_secret' => env('LETMESENDEMAIL_WEBHOOK_SECRET', ''),

        // Maximum allowed difference, in seconds, from the signed timestamp.
        'tolerance' => 300,
    ],

];
