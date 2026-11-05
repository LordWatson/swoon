<?php

declare(strict_types=1);

/*
 * Mail is the only channel that reaches a partner outside the app — invites,
 * unlink notices, export links all go this way — so the shell every email
 * inherits is asserted here, on the one email that has no feature behind it.
 */

use App\Mail\TestPingMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mime\Email;

it('names the product from config, in the subject and the body', function (): void {
    config()->set('swoon.name', 'Renamed');

    $mail = new TestPingMail;

    expect($mail->envelope()->subject)->toBe('Renamed is plugged in')
        ->and($mail->render())->toContain('Hello from Renamed');
});

it('points the whole application at the product name', function (): void {
    // AppServiceProvider::boot() does this, so it covers Laravel's own
    // notifications too — password reset and email verification never pass
    // through SwoonMailable. If this breaks, email goes out nameless.
    expect(config('mail.from.name'))->toBe(config('swoon.name'))
        ->and(config('app.name'))->toBe(config('swoon.name'));
});

it('puts the product name on a real sent message', function (): void {
    $sent = Mail::to('partner@example.com')->send(new TestPingMail);

    $message = $sent?->getSymfonySentMessage()->getOriginalMessage();

    expect($message)->toBeInstanceOf(Email::class);
    assert($message instanceof Email);

    expect($message->getFrom()[0]->getName())->toBe(config('swoon.name'))
        ->and($message->getFrom()[0]->getAddress())->toBe(config('mail.from.address'));
});

it('sends the ping to the address given', function (): void {
    Mail::fake();

    $this->artisan('swoon:mail-test', ['email' => 'partner@example.com'])
        ->assertSuccessful();

    Mail::assertSent(TestPingMail::class, fn (TestPingMail $mail): bool => $mail->hasTo('partner@example.com'));
});

it('sends nothing when the address is not an address', function (): void {
    Mail::fake();

    $this->artisan('swoon:mail-test', ['email' => 'not-an-email'])
        ->assertFailed();

    Mail::assertNothingSent();
});

it('leaves the unsigned Resend webhook route unregistered', function (): void {
    // The package signature-checks that route only when a webhook secret is
    // set, so until bounce handling exists it must not be reachable at all.
    expect(Route::has('resend.webhook'))->toBeFalse();
});
