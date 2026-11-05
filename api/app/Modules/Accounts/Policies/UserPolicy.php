<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Policies;

use App\Models\User;

/**
 * Who may touch a user record.
 *
 * Yourself, and nobody else. A partner may *view* profile basics through a
 * Resource built for that purpose — this policy is about the account, and no
 * partner may ever edit or delete the other's (module.md § Permissions).
 */
final class UserPolicy
{
    public function view(User $user, User $subject): bool
    {
        return $user->is($subject);
    }

    public function update(User $user, User $subject): bool
    {
        return $user->is($subject);
    }

    public function delete(User $user, User $subject): bool
    {
        return $user->is($subject);
    }

    public function export(User $user, User $subject): bool
    {
        return $user->is($subject);
    }

    /**
     * Viewing a partner's profile basics — name, colour, avatar, Love Map.
     * Never their email, social ids, or anything they marked private.
     */
    public function viewProfile(User $user, User $subject): bool
    {
        return $user->is($subject) || $user->partner()?->is($subject) === true;
    }
}
