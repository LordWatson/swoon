<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Proves the mail transport works, end to end, without involving a feature.
 * Sent only by `artisan swoon:mail-test`; nothing in the app dispatches it.
 */
final class TestPingMail extends SwoonMailable
{
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('swoon.name').' is plugged in',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.ping',
            with: ['product' => (string) config('swoon.name')],
        );
    }
}
