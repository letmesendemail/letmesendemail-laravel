<?php

declare(strict_types=1);

return [

    'api_key' => env('LETMESENDEMAIL_API_KEY', ''),

    'base_url' => env('LETMESENDEMAIL_BASE_URL', 'https://letmesend.email/api/v1'),

    'timeout' => (int) env('LETMESENDEMAIL_TIMEOUT', 30),

    'retries' => (int) env('LETMESENDEMAIL_RETRIES', 0),

    'webhooks' => [
        'enabled' => env('LETMESENDEMAIL_WEBHOOKS_ENABLED', false),
        'path' => env('LETMESENDEMAIL_WEBHOOK_PATH', '/webhooks/letmesendemail'),
        'middleware' => ['letmesendemail.webhook'],
        'signing_secret' => env('LETMESENDEMAIL_WEBHOOK_SECRET', ''),
        'tolerance' => 300,
    ],

];
