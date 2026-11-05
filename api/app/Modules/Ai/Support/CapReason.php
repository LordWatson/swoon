<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

/**
 * Why a request was refused, which decides what the couple is told.
 *
 * Four reasons and four different pieces of copy, because "that's your date
 * plans for this month" and "something's busy at our end" are not the same
 * news and should not read as if they were.
 */
enum CapReason: string
{
    case Allowed = 'allowed';

    /** Used everything the tier includes this month. Comes back on the 1st. */
    case MonthlyCap = 'monthly_cap';

    /** The tier does not include this feature at all. Nothing to wait for. */
    case NotOnThisTier = 'not_on_this_tier';

    /**
     * This couple has spent more this month than the runaway guard allows.
     *
     * Far above the feature caps, so in normal use it is unreachable: hitting
     * it means a bug or abuse, not a keen couple.
     */
    case CoupleCostGuard = 'couple_cost_guard';

    /** Everyone's spending today crossed the global ceiling. Our problem. */
    case GlobalCostGuard = 'global_cost_guard';
}
