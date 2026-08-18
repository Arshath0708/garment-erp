<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Gemini (document OCR)
    |--------------------------------------------------------------------------
    | Used by Export Document checklist "Extract with AI" — reads uploaded
    | scans (B/L, LEO, container/seal) and suggests reference fields.
    */
    'gemini' => [
        'key'        => env('GEMINI_API_KEY'),
        'model'      => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        // Set GEMINI_SSL_VERIFY=false only on local Windows if CA certs are missing.
        'verify_ssl' => filter_var(env('GEMINI_SSL_VERIFY', true), FILTER_VALIDATE_BOOL),
    ],

];
