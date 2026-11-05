<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Mail;

use App\Mail\SwoonMailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * "Your copy is ready."
 *
 * Says how long the link lasts, because a link that silently stops working is
 * worse than one that told you it would.
 */
final class ExportReadyMail extends SwoonMailable
{
    public function __construct(
        private readonly string $firstName,
        private readonly string $url,
        private readonly int $ttlHours,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.config('swoon.name').' data is ready');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.export-ready',
            with: [
                'firstName' => $this->firstName,
                'url' => $this->url,
                'ttlHours' => $this->ttlHours,
                'product' => (string) config('swoon.name'),
            ],
        );
    }
}
