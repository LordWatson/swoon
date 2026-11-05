<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Policies;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;

/**
 * Who may touch a couple.
 *
 * One rule underneath all of these: **a live membership**. Not "was once a
 * member" — an ex-partner must not be able to read the couple's shared content
 * after unlinking (rule 4), and `hasActiveMember()` is what makes that true.
 *
 * Both partners are equal. There is no owner, no admin, and no partner who can
 * overrule the other: either may change the couple's settings, and either may
 * unlink without the other's consent (rule 3).
 */
final class CouplePolicy
{
    public function view(User $user, Couple $couple): bool
    {
        return $couple->hasActiveMember($user);
    }

    public function update(User $user, Couple $couple): bool
    {
        return $couple->hasActiveMember($user);
    }

    /**
     * Unlinking needs no consent from the other partner. That is a safety
     * requirement, not a convenience: requiring consent would let a
     * controlling partner trap someone (rule 3).
     */
    public function unlink(User $user, Couple $couple): bool
    {
        return $couple->isLinked() && $couple->hasActiveMember($user);
    }
}
