<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Mail;

use App\Mail\SwoonMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Telling the other partner their shared space has closed.
 *
 * The hardest email in the product to get right. Rules it follows (rule 5):
 *
 * - **Plainly, and without blame.** No reason is given, because we do not know
 *   one and guessing would be worse than silence.
 * - **Past tense only.** It has already happened; nothing here implies it is
 *   pending or could be argued with.
 * - **No "why", no "contact them", no "undo".** Any of those would turn a
 *   safety feature into a negotiation.
 * - It says what happened to their own things, because that is the one
 *   question they will actually have.
 */
final class UnlinkNoticeMail extends SwoonMailable
{
    public function __construct(private readonly string $recipientFirstName) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your shared space has closed');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.unlink-notice',
            with: [
                'firstName' => $this->recipientFirstName,
                'product' => (string) config('swoon.name'),
            ],
        );
    }
}
