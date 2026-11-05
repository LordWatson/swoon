<?php

declare(strict_types=1);

$paidCaps = [
    'date_plan' => 20,
    'weekly_recap' => 5,
    'insights' => 10,
];

return [

    /*
    |---------------------------------------------------------------------------
    | Models
    |---------------------------------------------------------------------------
    |
    | Model IDs NEVER appear in a job, action or prompt — they are referenced by
    | the keys below (e.g. config('ai.models.strong')). That way a model upgrade
    | is a config change, and tests can pin a fake driver.
    |
    |   strong — date planning, weekly recap, insights, book curation.
    |            Quality is visible to the couple; worth the tokens.
    |   light  — classification, safety pre-screen, tagging, short rewrites.
    |            High volume, low judgement.
    |
    | Pricing at time of writing (USD per 1M tokens, input/output):
    |   claude-opus-5-5    4.00 / 20.00
    |   claude-sonnet-5-5  2.00 / 10.00   (middle option if strong is too costly)
    |   claude-haiku-5-5   0.10 /  0.50
    |
    */

    'models' => [
        'strong' => env('AI_MODEL_STRONG', 'claude-opus-5-5'),
        'light' => env('AI_MODEL_LIGHT', 'claude-haiku-5-5'),
    ],

    /*
    |---------------------------------------------------------------------------
    | Per-task model + effort mapping
    |---------------------------------------------------------------------------
    |
    | Each AI task names a model key and an effort level, so cost tuning happens
    | here rather than in the code that builds the prompt.
    |
    */

    'tasks' => [
        'date_plan' => ['model' => 'strong', 'effort' => 'high', 'max_tokens' => 8000],
        'weekly_recap' => ['model' => 'strong', 'effort' => 'medium', 'max_tokens' => 4000],
        'insights' => ['model' => 'strong', 'effort' => 'medium', 'max_tokens' => 2000],
        'date_pack' => ['model' => 'strong', 'effort' => 'medium', 'max_tokens' => 8000],
        'year_in_moments' => ['model' => 'strong', 'effort' => 'high', 'max_tokens' => 8000],
        'safety_screen' => ['model' => 'light', 'effort' => 'low', 'max_tokens' => 512],
        'moment_tagging' => ['model' => 'light', 'effort' => 'low', 'max_tokens' => 512],
        'spark_question' => ['model' => 'light', 'effort' => 'low', 'max_tokens' => 512],
    ],

    /*
    |---------------------------------------------------------------------------
    | Driver
    |---------------------------------------------------------------------------
    |
    | "anthropic" in every real environment. "fake" is used by the test suite and
    | returns canned fixtures from tests/Fixtures/Ai — no network, no cost.
    |
    */

    'driver' => env('AI_DRIVER', 'anthropic'),

    /*
    |---------------------------------------------------------------------------
    | What laravel/ai adds to this file
    |---------------------------------------------------------------------------
    |
    | laravel/ai merges its own config under this same 'ai' key, so alongside
    | everything above there is also 'default', 'caching', 'remote_files',
    | 'conversations' and 'providers' — none of which collide with ours, and
    | all of which can be overridden here by naming the key.
    |
    | The Anthropic API key therefore already has a home at
    | config('ai.providers.anthropic.key'), reading ANTHROPIC_API_KEY. There is
    | deliberately no second copy of it here: one secret with two config paths
    | is one of them going stale. Server-side only either way — this key must
    | NEVER reach the mobile app.
    |
    */

    /*
    |---------------------------------------------------------------------------
    | Fair-use caps, per couple per calendar month, by entitlement tier
    |---------------------------------------------------------------------------
    |
    | Enforced server-side BEFORE a job is dispatched. Hitting a cap is a kind
    | message, never an error (UX rule 8).
    |
    */

    'caps' => [
        'spark' => [
            'date_plan' => 1,
            'weekly_recap' => 0,
            'insights' => 0,
        ],
        // Together and Forever share one allowance, defined once so the two
        // can never drift apart by half an edit. Give a tier its own array the
        // day it is genuinely meant to differ.
        'together' => $paidCaps,
        'forever' => $paidCaps,
    ],

    /*
    |---------------------------------------------------------------------------
    | Runaway cost guard
    |---------------------------------------------------------------------------
    |
    | A hard ceiling above the feature caps. In normal use it is never reached;
    | if it is, something is wrong and we want to know before the bill arrives.
    | Stored in pence to avoid floats.
    |
    */

    'cost_guard' => [
        'couple_monthly_pence' => env('AI_COST_GUARD_COUPLE_PENCE', 500),
        'global_daily_pence' => env('AI_COST_GUARD_GLOBAL_PENCE', 50000),
    ],

    /*
    |---------------------------------------------------------------------------
    | Prices used to compute the cost we log in ai_usage
    |---------------------------------------------------------------------------
    |
    | Kept here so ai_usage.cost is comparable over time even after provider
    | prices change; update alongside a model change.
    |
    | The unit is in the key on purpose. Provider prices are quoted in US
    | dollars, every cap and budget in this app is in pence, and the two are
    | NOT interchangeable — cost_guard.couple_monthly_pence is 500, which is
    | five pounds, not five hundred dollars. Convert with usd_to_pence before
    | comparing anything here to anything there. Whole numbers only, so the
    | arithmetic matches the rest of the app (docs/ARCHITECTURE.md § 3).
    |
    */

    'pricing_usd_cents_per_mtok' => [
        'claude-opus-5-5' => ['input' => 400, 'output' => 2000],
        'claude-sonnet-5-5' => ['input' => 200, 'output' => 1000],
        'claude-haiku-5-5' => ['input' => 10, 'output' => 50],
    ],

    // One dollar in pence. A rough, deliberately conservative rate: this
    // decides when spending stops, so erring high stops us a little early
    // rather than a lot late. Review it when the exchange rate moves far.
    'usd_to_pence' => env('AI_USD_TO_PENCE', 80),

    /*
    |---------------------------------------------------------------------------
    | Safety
    |---------------------------------------------------------------------------
    |
    | SafetyGuard runs on every user-facing generation. When it trips, the AI
    | response is replaced by a short, kind signpost to UK support services and
    | nothing else is generated. See docs/AI_PROMPTS.md.
    |
    */

    'safety' => [
        // There is deliberately no "enabled" flag. SafetyGuard runs on every
        // user-facing generation and fails closed; a switch would make a
        // documented non-negotiable one environment variable away from off.
        // Tests that must not reach a model use AI_DRIVER=fake.

        // UK support services shown when SafetyGuard trips.
        'signposts' => [
            [
                'name' => 'Samaritans',
                'detail' => 'Free, 24/7, any time',
                'phone' => '116 123',
                'url' => 'https://www.samaritans.org',
            ],
            [
                'name' => 'National Domestic Abuse Helpline',
                'detail' => 'Free, 24/7, run by Refuge',
                'phone' => '0808 2000 247',
                'url' => 'https://www.nationaldahelpline.org.uk',
            ],
            [
                'name' => "Respect Men's Advice Line",
                'detail' => 'For men experiencing abuse',
                'phone' => '0808 8010 327',
                'url' => 'https://mensadviceline.org.uk',
            ],
            [
                'name' => 'Galop',
                'detail' => 'LGBT+ anti-abuse support',
                'phone' => '0800 999 5428',
                'url' => 'https://galop.org.uk',
            ],
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Prompt registry
    |---------------------------------------------------------------------------
    |
    | Prompts are versioned files, not strings in code. The registry resolves a
    | name + version to a file under the path below. docs/AI_PROMPTS.md is the
    | human index of everything in here.
    |
    */

    'prompts' => [
        'path' => resource_path('prompts'),
        // Pin a version per prompt so a new file does not silently go live.
        'active' => [
            'date_plan' => 'v1',
            'weekly_recap' => 'v1',
            'insights' => 'v1',
            'safety_screen' => 'v1',
        ],
    ],

];
