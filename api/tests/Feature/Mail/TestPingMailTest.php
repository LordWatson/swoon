<?php

declare(strict_types=1);

/*
 * Mail is the only channel that reaches a partner outside the app — invites,
 * unlink notices, export links all go this way — so the shell every email
 * inherits is asserted here, on the one email that has no feature behind it.
 */

use App\Mail\TestPingMail;
use Illuminate\Support\Facades\Mail;

it('names the product from config, in the subject and the body', function (): void {
    config()->set('swoon.name', 'Renamed');

    $mail = new TestPingMail;

    expect($mail->envelope()->subject)->toBe('Renamed is plugged in')
        ->and($mail->render())->toContain('Hello from Renamed');
});

it('takes the from-name from config, not from MAIL_FROM_NAME', function (): void {
    // The env var interpolates ${SWOON_NAME}; a deploy that forgets it would
    // otherwise send mail from a nameless address. This is why SwoonMailable
    // exists, so it is the assertion that must not rot.
    config()->set('mail.from.name', '');
    config()->set('swoon.name', 'Swoon');

    expect((new TestPingMail)->from)->toHaveCount(1)
        ->and((new TestPingMail)->from[0]['name'])->toBe('Swoon')
        ->and((new TestPingMail)->from[0]['address'])->toBe(config('mail.from.address'));
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
