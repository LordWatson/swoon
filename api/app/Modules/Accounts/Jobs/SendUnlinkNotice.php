<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Jobs;

use App\Modules\Accounts\Mail\UnlinkNoticeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Email the other partner that the shared space has closed.
 *
 * Takes the address and first name as plain strings rather than a User, so the
 * job still works if the account is deleted between the unlink and the worker
 * picking it up — which is a realistic sequence, since "unlink then delete" is
 * exactly what someone leaving does.
 */
final class SendUnlinkNotice implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $email,
        private readonly string $firstName,
    ) {}

    public function handle(): void
    {
        Mail::to($this->email)->send(new UnlinkNoticeMail($this->firstName));
    }
}
