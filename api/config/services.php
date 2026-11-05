<?php

declare(strict_types=1);

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

    /*
    |---------------------------------------------------------------------------
    | Sign in with Apple
    |---------------------------------------------------------------------------
    |
    | `client_id` is the audience an Apple identity token must carry: the app's
    | bundle id for a native sign-in, or the Services ID for the web. With no
    | value configured, Apple sign-in REFUSES every token rather than accepting
    | any — see OidcTokenVerifier.
    |
    */

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'key_id' => env('APPLE_KEY_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Google sign-in
    |---------------------------------------------------------------------------
    |
    | One client id per platform. A token minted for the iOS app carries the
    | iOS audience and a token from Android carries the Android one, and both
    | are legitimately ours — so all three are accepted and nothing else is.
    |
    */

    'google' => [
        'client_id_ios' => env('GOOGLE_CLIENT_ID_IOS'),
        'client_id_android' => env('GOOGLE_CLIENT_ID_ANDROID'),
        'client_id_web' => env('GOOGLE_CLIENT_ID_WEB'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
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

];
