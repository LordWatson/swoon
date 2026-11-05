<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

/**
 * How an AI call ended. Every one of these gets a row in `ai_usage`.
 *
 * Five, and the list is closed: if something new can happen, it needs a case
 * here and a test, because "no code path reaches the model without logging"
 * only means anything while every ending has a name.
 */
enum AiOutcome: string
{
    /** The model answered, the shape was right, the screen was happy. */
    case Ok = 'ok';

    /** Two replies in the wrong shape. We paid for both. */
    case InvalidOutput = 'invalid_output';

    /**
     * SafetyGuard found something, or could not tell.
     *
     * A row with this outcome carries **no couple and no user** (ADR-027).
     */
    case SafetyTripped = 'safety_tripped';

    /** Refused before dispatch: the monthly cap or the cost guard. No call. */
    case Capped = 'capped';

    /** Anything else: transport, a missing prompt, a guard that broke. */
    case Error = 'error';

    /**
     * Whether a row for this outcome may name the couple it belongs to.
     *
     * The one exception is the point of ADR-027: the cost of a safety screen
     * still has to be logged, but nothing in this database should say that
     * this couple tripped the guard on this date.
     */
    public function mayNameTheCouple(): bool
    {
        return $this !== self::SafetyTripped;
    }
}
