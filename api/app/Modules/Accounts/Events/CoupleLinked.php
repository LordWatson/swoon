<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Events;

use App\Modules\Accounts\Models\Couple;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A couple exists.
 *
 * The starting gun for most of the product: the 7-day trial, the first
 * milestones, the first Daily Spark. Fired **inside** the accept transaction's
 * commit, exactly once per couple — listeners must be idempotent anyway, but
 * nothing should be relying on being called twice.
 */
final class CoupleLinked
{
    use Dispatchable;

    public function __construct(public readonly Couple $couple) {}
}
