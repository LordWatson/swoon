<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The base every Swoon email extends.
 *
 * The sender name is NOT set here. AppServiceProvider::boot() points
 * mail.from.name at config('swoon.name') for the whole application, which also
 * covers Laravel's own notifications — password reset, email verification —
 * that never pass through this class. Setting it again in a constructor would
 * have protected only half the mail, and silently lost the name for any
 * subclass that forgot to call parent::__construct().
 *
 * What is left is the shell every email shares: queueable, and safe to pass a
 * model to. Shared headers, tags or a footer belong here when they arrive.
 */
abstract class SwoonMailable extends Mailable
{
    use Queueable, SerializesModels;
}
