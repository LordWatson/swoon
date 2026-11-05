<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Events;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A couple has ended.
 *
 * Carries who did it, because the notice email goes to the *other* partner —
 * and because nothing else in the app should have to work that out.
 */
final class CoupleUnlinked
{
    use Dispatchable;

    public function __construct(
        public readonly Couple $couple,
        public readonly User $unlinkedBy,
        public readonly ?User $otherPartner,
    ) {}
}
