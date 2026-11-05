<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Mail;

use App\Mail\SwoonMailable;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Alex would like to share a space with you."
 *
 * Carries the inviter's first name only, like the preview endpoint: this email
 * can be forwarded anywhere, so it holds nothing the link does not already
 * imply. Sent as a reminder too, three days on, with one line changed.
 */
final class InviteMail extends SwoonMailable
{
    public function __construct(
        private readonly CoupleInvite $invite,
        private readonly bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $name = str($this->invite->inviter->name)->before(' ')->toString();

        return new Envelope(
            subject: $this->isReminder
                ? $name.' is still waiting for you 💞'
                : $name.' would like to share a space with you 💞',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invite',
            with: [
                'firstName' => str($this->invite->inviter->name)->before(' ')->toString(),
                'code' => $this->invite->code,
                'link' => sprintf(
                    'https://%s/invite/%s',
                    config('swoon.deep_link.host'),
                    $this->invite->code,
                ),
                'isReminder' => $this->isReminder,
                'product' => (string) config('swoon.name'),
            ],
        );
    }
}
