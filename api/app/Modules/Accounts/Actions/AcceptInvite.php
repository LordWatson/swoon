<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Events\CoupleLinked;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Accept an invite and create the couple.
 *
 * Every refusal here is a real case, and each returns a plain message rather
 * than a silent no-op: accepting your own code, accepting while already
 * coupled, a code that has expired, been used, or been revoked, and an inviter
 * who has linked with someone else since sending it.
 *
 * The concurrency guard is the conditional UPDATE, not the checks above it. Two
 * people accepting the same code at the same moment would both pass a
 * read-then-write; `update(... where accepted_at is null)` can only succeed
 * once, and the loser is told the code has been used. The one-couple invariant
 * in the database is the backstop underneath that.
 */
final class AcceptInvite
{
    /**
     * @throws ValidationException
     */
    public function handle(CoupleInvite $invite, User $accepter): Couple
    {
        $inviter = $invite->inviter;

        $this->assertAcceptable($invite, $inviter, $accepter);

        return DB::transaction(function () use ($invite, $inviter, $accepter): Couple {
            // Claim the code. Succeeds for exactly one caller.
            $claimed = CoupleInvite::whereKey($invite->getKey())
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->update([
                    'accepted_at' => now(),
                    'accepted_by_user_id' => $accepter->getKey(),
                ]);

            if ($claimed === 0) {
                throw ValidationException::withMessages([
                    'code' => ['That code has already been used.'],
                ]);
            }

            $couple = Couple::create(['linked_at' => now()]);

            [$inviterColour, $accepterColour] = $this->colours($inviter, $accepter);

            $this->join($couple, $inviter, 'a', $inviterColour);
            $this->join($couple, $accepter, 'b', $accepterColour);

            CoupleLinked::dispatch($couple);

            return $couple;
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertAcceptable(CoupleInvite $invite, User $inviter, User $accepter): void
    {
        $refusal = match (true) {
            // Rule 8. Without this, a curious user links with themselves and
            // every "your partner" string in the app becomes nonsense.
            $inviter->is($accepter) => 'That is your own invite 😄 Send it to your partner instead.',
            $invite->revoked_at !== null => 'That invite has been cancelled.',
            $invite->accepted_at !== null => 'That code has already been used.',
            $invite->isExpired() => 'That code has expired. Ask for a fresh one 💞',
            // Rule 1: a clear message, never a silent re-link.
            $accepter->isCoupled() => 'You are already linked with someone 💞',
            $inviter->isCoupled() => $inviter->name.' has already linked with someone.',
            default => null,
        };

        if ($refusal !== null) {
            throw ValidationException::withMessages(['code' => [$refusal]]);
        }
    }

    /**
     * Partner colours, which must differ (rule 12).
     *
     * The inviter keeps whatever they already chose. The joining partner keeps
     * theirs only if it does not clash; otherwise they are given the first free
     * one and can change it in settings.
     *
     * @return array{string, string}
     */
    private function colours(User $inviter, User $accepter): array
    {
        $palette = ['rose', 'lavender', 'apricot', 'sage'];

        $inviterColour = $inviter->colour ?? 'rose';

        $accepterColour = $accepter->colour !== null && $accepter->colour !== $inviterColour
            ? $accepter->colour
            : (collect($palette)->first(fn (string $c): bool => $c !== $inviterColour) ?? 'lavender');

        return [$inviterColour, $accepterColour];
    }

    private function join(Couple $couple, User $user, string $role, string $colour): void
    {
        $couple->members()->attach($user, [
            'role' => $role,
            'joined_at' => now(),
            'left_at' => null,
        ]);

        if ($user->colour !== $colour) {
            $user->forceFill(['colour' => $colour])->save();
        }
    }
}
