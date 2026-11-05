<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Events\CoupleUnlinked;
use App\Modules\Accounts\Models\Couple;
use Illuminate\Support\Facades\DB;

/**
 * End a couple. Immediately, and without the other partner's consent.
 *
 * **That is a safety requirement, not a convenience** (rule 3). Requiring the
 * other partner to agree — or even to be told first — would let a controlling
 * partner trap someone in a shared space they cannot leave. So there is no
 * pending state, no grace period, no "are you sure, we've told them" step: the
 * request that asks for it is the request that does it.
 *
 * It **detaches** rather than deletes (rule 4). The couple record stays, closed,
 * holding the shared content. Each partner keeps what they personally authored;
 * neither can read the couple's shared content afterwards, which falls out of
 * `CouplePolicy` asking for a *live* membership rather than any membership.
 */
final class UnlinkCouple
{
    public function handle(Couple $couple, User $unlinkedBy): void
    {
        $otherPartner = $couple->partnerFor($unlinkedBy);
        $now = now();

        DB::transaction(function () use ($couple, $now): void {
            // Every live membership ends, which also releases both partners
            // from the one-couple invariant so either can link again.
            $couple->members()->newPivotQuery()->whereNull('left_at')->update(['left_at' => $now]);

            $couple->forceFill(['unlinked_at' => $now])->save();
        });

        // After the commit: the other partner is told it has happened, never
        // that it is about to (rule 5).
        CoupleUnlinked::dispatch($couple, $unlinkedBy, $otherPartner);
    }
}
