<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mailifica API Key
    |--------------------------------------------------------------------------
    |
    | This key is used to authenticate requests to the Mailifica API.
    | You can find your API key in the Mailifica Dashboard.
    |
    */
    'api_key' => env('MAILIFICA_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Mailifica Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL endpoint for the Mailifica API. Defaults to the official
    | production API. Can be overridden for self-hosted instances or testing.
    |
    */
    'base_url' => env('MAILIFICA_BASE_URL', 'https://api.mailifica.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests sent to Mailifica.
    |
    */
    'timeout' => env('MAILIFICA_TIMEOUT', 30.0),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for verifying and handling incoming webhooks from Mailifica.
    |
    */
    'webhook' => [
        'secret' => env('MAILIFICA_WEBHOOK_SECRET'),
        'path' => env('MAILIFICA_WEBHOOK_PATH', '/mailifica/webhook'),
        'tolerance' => env('MAILIFICA_WEBHOOK_TOLERANCE', 300), // in seconds
    ],
];
