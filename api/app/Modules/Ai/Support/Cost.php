<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\Data\TextUsage;

/**
 * What a call cost us, in millipence.
 *
 * Three units meet here and none of them are interchangeable, which is why
 * each one is named wherever it appears:
 *
 * - provider prices are quoted in **US dollars per million tokens**, held in
 *   config as **whole US cents** per million (`400` is $4.00);
 * - the app's budgets are in **pence**;
 * - this returns **millipence**, because a `safety_screen` call costs about
 *   0.006p and rounding it to pence would log zero for every screen the
 *   product makes (ADR-026).
 *
 * The prices live in config rather than being fetched, so a row written today
 * is still comparable with one from last year after the provider changes its
 * pricing — which is the only way "what did this feature cost us" stays a
 * question with an answer.
 */
final class Cost
{
    /**
     * The exchange: tokens x cents-per-million x pence-per-dollar.
     *
     *   pence = tokens / 1e6 x cents / 100 x usd_to_pence
     *   millipence = tokens x cents x usd_to_pence / 100_000
     *
     * Done in one division so there is no intermediate rounding. The
     * magnitudes are far below 2^53, so the float arithmetic is exact and
     * `round()` is the only approximation.
     */
    public static function millipence(string $model, TextUsage $usage): int
    {
        $prices = config('ai.pricing_usd_cents_per_mtok.'.$model);

        if (! is_array($prices)) {
            // Not fatal: refusing to answer a couple because a price is
            // missing would be the wrong trade. But it under-reports, so it
            // is loud, and a test asserts every configured model has a price.
            Log::warning('No AI price configured for model, cost logged as zero.', ['model' => $model]);

            return 0;
        }

        $cents = (int) ($prices['input'] ?? 0) * $usage->inputTokens
            + (int) ($prices['output'] ?? 0) * $usage->outputTokens;

        // shortcut: cached input tokens are priced as ordinary input, which
        // over-reports once prompt caching is on (a cache read is a tenth of
        // the price). Erring high stops spending a little early rather than a
        // lot late, as `usd_to_pence` already does. Upgrade when the first
        // prompt is cached: split `uncachedInputTokens()` from the cache
        // counts and give each its own rate.
        return (int) round($cents * (int) config('ai.usd_to_pence') / 100_000);
    }

    /**
     * Millipence to pence, for comparing against a budget or showing a person.
     *
     * Rounds up. A guard that rounds a spend down lets the last call through
     * for free, every time, and the point of a guard is to be slightly mean.
     */
    public static function toPence(int $millipence): int
    {
        return intdiv($millipence + 999, 1000);
    }
}
