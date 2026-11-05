<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\TestPingMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Mime\Message;

/**
 * Sends the one test email, so the transport can be proved before any feature
 * depends on it. With MAIL_MAILER=log the result lands in the log; with
 * `resend` and a verified domain it reaches a real inbox.
 */
final class MailTest extends Command
{
    protected $signature = 'swoon:mail-test {email : Where to send the ping}';

    protected $description = 'Send a test email to confirm the mail transport works';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        if (Validator::make(['email' => $email], ['email' => 'required|email'])->fails()) {
            $this->components->error("[{$email}] is not an email address.");

            return self::FAILURE;
        }

        $sent = Mail::to($email)->send(new TestPingMail);

        $mailer = (string) config('mail.default');

        $this->components->info("Ping sent to {$email} via the [{$mailer}] mailer.");

        if ($mailer === 'log') {
            $this->components->warn('That mailer only writes to the log — check storage/logs.');
        }

        // Resend stamps its own id on the message. Printing it is the only way
        // to tell "the API accepted it" from "it reached an inbox" — the id is
        // what you look up in the Resend dashboard.
        $message = $sent?->getSymfonySentMessage()->getOriginalMessage();

        $resendId = $message instanceof Message
            ? $message->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString()
            : null;

        if ($resendId !== null) {
            $this->components->twoColumnDetail('Resend email id', $resendId);
        }

        return self::SUCCESS;
    }
}
