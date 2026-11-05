<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Product name
    |---------------------------------------------------------------------------
    |
    | "Swoon" is a working name. This is the ONE place it lives on the backend
    | (the mobile side has mobile/src/config/app.ts). Renaming the product means
    | changing these two values — never a find-and-replace across the codebase.
    |
    | Anything user-facing (emails, AI prompts, store copy) reads it from here.
    |
    */

    'name' => env('SWOON_NAME', 'Swoon'),

    'tagline' => env('SWOON_TAGLINE', 'Your relationship concierge'),

    /*
    |---------------------------------------------------------------------------
    | Support & legal
    |---------------------------------------------------------------------------
    */

    'support_email' => env('SWOON_SUPPORT_EMAIL', 'hello@swoon.test'),

    'urls' => [
        'marketing' => env('SWOON_URL_MARKETING', 'https://swoon.test'),
        'privacy' => env('SWOON_URL_PRIVACY', 'https://swoon.test/privacy'),
        'terms' => env('SWOON_URL_TERMS', 'https://swoon.test/terms'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Deep linking
    |---------------------------------------------------------------------------
    |
    | Used to build partner invite links. The scheme must match the Expo app's
    | "scheme" in app.config.ts, and the universal-link host must be covered by
    | the Apple App Site Association / Android assetlinks files.
    |
    */

    'deep_link' => [
        'scheme' => env('SWOON_DEEP_LINK_SCHEME', 'swoon'),
        'host' => env('SWOON_DEEP_LINK_HOST', 'link.swoon.test'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Couple invites
    |---------------------------------------------------------------------------
    */

    'invites' => [
        // Short, human-readable code the partner can type if the link fails.
        'code_length' => 8,
        'expires_after_days' => 14,
        // A user may hold one active outbound invite at a time.
        'max_active_per_user' => 1,
    ],

    /*
    |---------------------------------------------------------------------------
    | Nudges (UX rule 4)
    |---------------------------------------------------------------------------
    |
    | A "nudge" emails the other partner that something is waiting for them.
    | Rate-limited per couple per item so it can never become pestering.
    |
    */

    'nudges' => [
        'cooldown_hours' => 24,
    ],

    /*
    |---------------------------------------------------------------------------
    | Undo windows (UX rule 10)
    |---------------------------------------------------------------------------
    */

    'undo' => [
        'moment_delete_seconds' => 10,
    ],

];
