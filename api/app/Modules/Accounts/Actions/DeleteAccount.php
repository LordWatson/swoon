<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Jobs\PurgeDeletedUserData;
use Illuminate\Support\Facades\DB;

/**
 * Delete an account. Immediately from the user's point of view.
 *
 * What happens inside the request — so that by the time the response lands,
 * the account is gone as far as anyone can tell:
 *
 * 1. **Every token is revoked**, so every signed-in device stops working now
 *    rather than when the queue catches up.
 * 2. **The couple is unlinked**, if there is one. A partner left "coupled" to a
 *    deleted account would see a shared space with nobody in it.
 * 3. **Open invites are revoked**, so a code already shared cannot link
 *    somebody to an account that no longer exists.
 * 4. **The row is soft-deleted**, which is what makes sign-in fail.
 *
 * The slow part — scrubbing the personal data and removing everything the user
 * authored — runs in `PurgeDeletedUserData` on the queue, because it will
 * eventually involve media in object storage and must not hold up the request.
 */
final class DeleteAccount
{
    public function __construct(private readonly UnlinkCouple $unlink) {}

    public function handle(User $user): void
    {
        $couple = $user->couple();

        if ($couple !== null) {
            // Deleting implies leaving. Same rules as a plain unlink: it
            // happens now, the other partner is told it has happened, and no
            // consent is sought.
            $this->unlink->handle($couple, $user);
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->invites()->open()->update(['revoked_at' => now()]);
            $user->delete();
        });

        PurgeDeletedUserData::dispatch($user->getKey());
    }
}
