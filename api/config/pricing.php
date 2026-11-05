<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------------
    | Tiers
    |---------------------------------------------------------------------------
    |
    | Prices are in pence (GBP) so they are never floats. These values are for
    | display and for tests — the actual charge is made by Apple/Google via
    | RevenueCat, and the store's price is authoritative. If they diverge, the
    | store wins and this config is stale.
    |
    | See docs/PRICING.md for the reasoning behind each number.
    |
    */

    'tiers' => [
        'spark' => [
            'name' => 'Spark',
            'price_pence' => 0,
            'period' => null,
        ],
        'together_monthly' => [
            'name' => 'Together',
            'price_pence' => 999,
            'period' => 'month',
            'revenuecat_entitlement' => 'together',
        ],
        'together_annual' => [
            'name' => 'Together',
            'price_pence' => 7999,
            'period' => 'year',
            'revenuecat_entitlement' => 'together',
        ],
        'forever' => [
            'name' => 'Forever',
            'price_pence' => 19900,
            'period' => 'lifetime',
            'revenuecat_entitlement' => 'forever',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Trial
    |---------------------------------------------------------------------------
    |
    | Starts when the SECOND partner joins, not at sign-up — both partners need
    | to experience the paid product together for it to mean anything.
    | One per couple AND one per user, so trials cannot be farmed by re-linking.
    |
    */

    'trial' => [
        'days' => 7,
        'starts_on' => 'partner_joined',
        'entitlement' => 'together',
    ],

    /*
    |---------------------------------------------------------------------------
    | Free-tier limits
    |---------------------------------------------------------------------------
    */

    'limits' => [
        'spark' => [
            'moments' => 30,
            'photos_per_moment' => 1,
            'voice_notes' => 0,
        ],
        'together' => [
            'moments' => null, // unlimited
            'photos_per_moment' => 10,
            'voice_notes' => null,
        ],
        'forever' => [
            'moments' => null,
            'photos_per_moment' => 10,
            'voice_notes' => null,
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Add-ons
    |---------------------------------------------------------------------------
    |
    | "rail" is a hard constraint, not a preference:
    |   iap    — digital goods. MUST go through RevenueCat (store rules).
    |   stripe — physical goods and real-world services ONLY.
    |
    | Never offer an "iap" item through Stripe, and never link out of the app to
    | buy one. See docs/PRICING.md section 5 and docs/DECISIONS.md ADR-011.
    |
    */

    'addons' => [
        'book_standard' => ['name' => 'Year in Moments — hardback', 'price_pence' => 5900, 'rail' => 'stripe'],
        'book_premium' => ['name' => 'Year in Moments — lay-flat', 'price_pence' => 8900, 'rail' => 'stripe'],
        'concierge_simple' => ['name' => 'Concierge Occasion', 'price_pence' => 14900, 'rail' => 'stripe'],
        'concierge_standard' => ['name' => 'Concierge Occasion', 'price_pence' => 29900, 'rail' => 'stripe'],
        'concierge_premium' => ['name' => 'Concierge Occasion', 'price_pence' => 49900, 'rail' => 'stripe'],
        'gift_concierge' => ['name' => 'Gift Concierge', 'price_pence' => 4900, 'rail' => 'stripe'],
        'date_pack' => ['name' => 'Premium date pack', 'price_pence' => 499, 'rail' => 'iap'],
        'date_pack_large' => ['name' => 'Premium date pack', 'price_pence' => 999, 'rail' => 'iap'],
    ],

    'currency' => 'GBP',

];
