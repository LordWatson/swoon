<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Validation\ValidationException;

/**
 * Cancel an invite you sent.
 *
 * Revoking an already-used invite is refused rather than ignored: the couple it
 * created is not undone by cancelling the code, and quietly succeeding would
 * suggest otherwise.
 */
final class RevokeInvite
{
    /**
     * @throws ValidationException
     */
    public function handle(CoupleInvite $invite, User $inviter): void
    {
        if (! $invite->inviter->is($inviter)) {
            // The policy gate catches this first; belt and braces, because an
            // Action is also called from jobs and commands.
            throw ValidationException::withMessages(['code' => ['That is not your invite.']]);
        }

        if ($invite->accepted_at !== null) {
            throw ValidationException::withMessages([
                'code' => ['That invite has already been accepted.'],
            ]);
        }

        $invite->forceFill(['revoked_at' => now()])->save();
    }
}
