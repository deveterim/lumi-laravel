<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lumi API Key
    |--------------------------------------------------------------------------
    |
    | This key is used to authenticate requests to the Lumi API.
    | You can find your API key in the Lumi Dashboard.
    |
    */
    'api_key' => env('LUMI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Lumi Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL endpoint for the Lumi API. Defaults to the official
    | production API. Can be overridden for self-hosted instances or testing.
    |
    */
    'base_url' => env('LUMI_BASE_URL', 'https://api.lumi.email/v1'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests sent to Lumi.
    |
    */
    'timeout' => env('MAILIFICA_TIMEOUT', 30.0),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for verifying and handling incoming webhooks from Lumi.
    |
    */
    'webhook' => [
        'secret' => env('MAILIFICA_WEBHOOK_SECRET'),
        'path' => env('MAILIFICA_WEBHOOK_PATH', '/mailifica/webhook'),
        'tolerance' => env('MAILIFICA_WEBHOOK_TOLERANCE', 300), // in seconds
    ],
];
