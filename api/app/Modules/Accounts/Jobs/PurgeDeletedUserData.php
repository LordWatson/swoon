<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Scrub a deleted account down to a tombstone and remove what it authored.
 *
 * ## Why a tombstone and not a hard delete
 *
 * The row survives, emptied. Everything personal is gone — name, email,
 * password, social ids, avatar, date of birth, dietary and accessibility needs
 * — but the primary key stays, so **the other partner's shared content still
 * resolves**. A couple's shared memories are the other person's memories too;
 * hard-deleting the row would either cascade them away or leave dangling
 * references, and neither is acceptable (rule 9).
 *
 * What a tombstone looks like to the app: a member named "Someone", with no
 * contact details and no profile. Nothing identifies the person any more.
 *
 * ## EVERY MODULE MUST ADD ITSELF HERE
 *
 * This is the one place that knows what a user owns, and a module that forgets
 * to add its tables leaves personal data behind after a UK GDPR erasure
 * request. There is deliberately no registry or auto-discovery: a visible list
 * that fails a review is better than a clever mechanism that silently misses a
 * table. The list below is everything that exists today; the ones named in
 * `Accounts/module.md` rule 9 and not yet built are listed as TODOs with the
 * phase that creates them.
 */
final class PurgeDeletedUserData implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $userId) {}

    public function handle(): void
    {
        $user = User::withTrashed()->find($this->userId);

        if ($user === null || ! $user->trashed()) {
            // Not deleted, or already purged. Either way, nothing to do — and
            // purging a live account because a stale job got retried would be
            // the worst bug in the system.
            return;
        }

        DB::transaction(function () use ($user): void {
            $this->removeAuthoredContent($user);
            $this->tombstone($user);
        });
    }

    /**
     * Everything this user owns outright.
     *
     * Not the couple, and not the couple's shared content: that belongs to
     * both of them and the other partner keeps it.
     */
    private function removeAuthoredContent(User $user): void
    {
        // Tokens are already gone from the request, but a device that
        // authenticated in between would have made another.
        $user->tokens()->delete();

        // Invites they sent. The codes must stop working.
        $user->invites()->delete();

        // TODO P4: moments, moment_media, media objects in S3 (the files too,
        //          not just the rows).
        // TODO P6: check-in answers, love notes, daily spark answers.
        // TODO P9: gift vault items, bucket-list entries, love-map entries,
        //          wishlist items.
        // TODO P7: ai_generations and ai_usage rows attributable to this user.
        // Each of those is one line here plus one assertion in
        // tests/Feature/Accounts/DeletionTest.php.
    }

    /**
     * Empty the row, keep the key.
     */
    private function tombstone(User $user): void
    {
        $user->forceFill([
            'name' => 'Someone',
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'remember_token' => null,
            'apple_sub' => null,
            'google_sub' => null,
            'avatar_path' => null,
            'date_of_birth' => null,
            'dietary_needs' => null,
            'accessibility_needs' => null,
            'colour' => null,
        ])->save();
    }
}
