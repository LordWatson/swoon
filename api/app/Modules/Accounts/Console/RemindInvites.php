<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Console;

use App\Modules\Accounts\Jobs\SendInviteEmail;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Console\Command;

/**
 * The single invite reminder, three days in.
 *
 * `reminded_at` is nullable and set once, which is what makes "a single
 * reminder" true rather than a promise. Nothing here can send a second one:
 * the query excludes anything already reminded.
 *
 * Scheduled daily. A partner who has not joined after three days gets one
 * nudge and is then left alone — chasing people is not the brand
 * (docs/BRAND.md § 1, "never demanding of daily attention").
 */
final class RemindInvites extends Command
{
    protected $signature = 'invites:remind';

    protected $description = 'Send the one-off reminder for invites still unanswered after three days';

    public function handle(): int
    {
        $due = CoupleInvite::query()
            ->open()
            ->whereNotNull('email')
            ->whereNull('reminded_at')
            ->where('expires_at', '>', now())
            ->where('created_at', '<=', now()->subDays(3))
            ->get();

        foreach ($due as $invite) {
            SendInviteEmail::dispatch($invite, isReminder: true);
        }

        $this->components->info($due->count().' reminder(s) queued.');

        return self::SUCCESS;
    }
}
