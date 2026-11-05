<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Listeners;

use App\Modules\Accounts\Events\CoupleUnlinked;
use App\Modules\Accounts\Jobs\SendUnlinkNotice;

/**
 * Tell the other partner, if there is one with an address to tell.
 *
 * A social-only account may have no email at all, and the couple may already
 * have been closed — both are quiet no-ops rather than errors. Unlinking must
 * never fail because the notice could not be sent.
 */
final class NotifyPartnerOfUnlink
{
    public function handle(CoupleUnlinked $event): void
    {
        $partner = $event->otherPartner;

        if ($partner?->email === null) {
            return;
        }

        SendUnlinkNotice::dispatch(
            $partner->email,
            str($partner->name)->before(' ')->toString(),
        );
    }
}
