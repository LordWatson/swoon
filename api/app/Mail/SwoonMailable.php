<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The base every Swoon email extends.
 *
 * It exists for one guarantee: the from-name is the product name from
 * config/swoon.php, not MAIL_FROM_NAME. The env var interpolates ${SWOON_NAME},
 * so a deploy that forgets that variable would otherwise send mail from a
 * nameless address.
 *
 * A subclass with its own constructor must call parent::__construct().
 * A subclass that defines `from:` in its own envelope() overrides this.
 */
abstract class SwoonMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        $this->from(
            (string) config('mail.from.address'),
            (string) config('swoon.name'),
        );
    }
}
