<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Models\CoupleInvite;
use App\Modules\Accounts\Support\InviteCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create the one invite a user may have outstanding.
 *
 * "One active outbound invite" (rule 6) is enforced by a unique index on a
 * generated column, so this cannot cheat it — which is why an existing **open**
 * invite is revoked rather than ignored. An expired-but-open invite still
 * occupies that slot, and a user whose code ran out must obviously be able to
 * get another one.
 */
final class CreateInvite
{
    /**
     * @throws ValidationException
     */
    public function handle(User $inviter, ?string $email = null): CoupleInvite
    {
        if ($inviter->isCoupled()) {
            throw ValidationException::withMessages([
                'code' => ['You are already linked with someone 💞'],
            ]);
        }

        return DB::transaction(function () use ($inviter, $email): CoupleInvite {
            // Replacing rather than returning the old one: a person asking for
            // an invite wants a code they can read out now, and two live codes
            // for one person is the thing the index exists to prevent.
            $inviter->invites()->open()->update(['revoked_at' => now()]);

            return $inviter->invites()->create([
                'code' => $this->unusedCode(),
                'email' => $email,
                'expires_at' => now()->addDays((int) config('swoon.invites.expires_after_days')),
            ]);
        });
    }

    /**
     * A code nobody holds.
     *
     * The alphabet is 31 characters over 8 places, so a collision is around one
     * in 8.5e11 — but "unlikely" is not "handled", and a collision would be a
     * unique-constraint crash in front of a real person.
     */
    private function unusedCode(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $code = InviteCode::generate();

            if (! CoupleInvite::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw ValidationException::withMessages([
            'code' => ['We could not make you a code just now. Try again in a moment.'],
        ]);
    }
}
