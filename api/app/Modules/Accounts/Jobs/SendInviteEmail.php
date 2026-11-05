<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Jobs;

use App\Modules\Accounts\Mail\InviteMail;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Email an invite, or remind about one.
 *
 * Queued, like every email in the app: a sign-up must not wait on an SMTP
 * round trip. Re-checks the invite on the way out, because by the time the
 * worker picks this up the code may have been accepted or revoked — sending
 * then would be confusing at best.
 */
final class SendInviteEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly CoupleInvite $invite,
        private readonly bool $isReminder = false,
    ) {}

    public function handle(): void
    {
        $invite = $this->invite->fresh();

        if ($invite === null || $invite->email === null || ! $invite->isUsable()) {
            return;
        }

        Mail::to($invite->email)->send(new InviteMail($invite, $this->isReminder));

        if ($this->isReminder) {
            // Nullable-and-set-once is what makes "a single reminder" true,
            // rather than a counter nobody checks.
            $invite->forceFill(['reminded_at' => now()])->save();
        }
    }
}
