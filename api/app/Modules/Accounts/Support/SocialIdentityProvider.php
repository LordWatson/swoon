<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Turns an Apple or Google identity token into a verified `SocialIdentity`.
 *
 * The provider-specific parts only: which JWKS to trust, which audiences are
 * ours, and what each one does about nonces and names. The cryptography is in
 * `OidcTokenVerifier`.
 */
final class SocialIdentityProvider
{
    public function __construct(private readonly OidcTokenVerifier $verifier) {}

    /**
     * @throws InvalidIdentityToken
     */
    public function apple(string $identityToken, ?string $nonce = null, ?string $name = null): SocialIdentity
    {
        $claims = $this->verifier->verify(
            $identityToken,
            'https://appleid.apple.com/auth/keys',
            array_filter([(string) config('services.apple.client_id')]),
            ['https://appleid.apple.com'],
        );

        // Apple echoes back the nonce the app sent. Checking it is what stops a
        // token captured from one sign-in being replayed into another.
        $this->assertNonce($claims, $nonce);

        $email = isset($claims['email']) && is_string($claims['email']) ? $claims['email'] : null;

        return new SocialIdentity(
            provider: 'apple',
            subject: (string) $claims['sub'],
            email: $email,
            // A relay address forwards to the real one but is specific to this
            // app, so it must never be treated as an identifier for the person.
            isPrivateRelayEmail: $email !== null && Str::endsWith($email, '@privaterelay.appleid.com'),
            // Apple returns the name OUTSIDE the token, and only on the very
            // first authorisation. The client forwards it; capture it now or
            // never see it again.
            name: $this->cleanName($name),
        );
    }

    /**
     * @throws InvalidIdentityToken
     */
    public function google(string $identityToken, ?string $nonce = null): SocialIdentity
    {
        $claims = $this->verifier->verify(
            $identityToken,
            'https://www.googleapis.com/oauth2/v3/certs',
            // One client id per platform: an iOS token and an Android token
            // carry different audiences and both are legitimately ours.
            array_values(array_filter([
                (string) config('services.google.client_id_ios'),
                (string) config('services.google.client_id_android'),
                (string) config('services.google.client_id_web'),
            ])),
            ['accounts.google.com', 'https://accounts.google.com'],
        );

        $this->assertNonce($claims, $nonce);

        // Google will not tell us the email is verified unless it is, and an
        // unverified one must not be used to match an existing account.
        $verified = ($claims['email_verified'] ?? false) === true
            || ($claims['email_verified'] ?? null) === 'true';

        $email = $verified && isset($claims['email']) && is_string($claims['email'])
            ? $claims['email']
            : null;

        return new SocialIdentity(
            provider: 'google',
            subject: (string) $claims['sub'],
            email: $email,
            isPrivateRelayEmail: false,
            name: $this->cleanName(isset($claims['name']) && is_string($claims['name']) ? $claims['name'] : null),
        );
    }

    /**
     * The nonce must match what the app sent, and must not have been used
     * before.
     *
     * `Cache::add` is atomic, so it is also the replay check: the first use
     * claims the nonce and any later use finds it taken. The window is the
     * token's own lifetime plus slack — after that the token is expired anyway
     * and `JWT::decode` rejects it.
     *
     * @param  array<string, mixed>  $claims
     *
     * @throws InvalidIdentityToken
     */
    private function assertNonce(array $claims, ?string $expected): void
    {
        if ($expected === null) {
            return;
        }

        $actual = $claims['nonce'] ?? null;

        if (! is_string($actual) || ! hash_equals($expected, $actual)) {
            throw new InvalidIdentityToken('That sign-in could not be verified.');
        }

        $fresh = Cache::add('oidc-nonce:'.hash('sha256', $actual), true, 600);

        if (! $fresh) {
            throw new InvalidIdentityToken('That sign-in could not be verified.');
        }
    }

    private function cleanName(?string $name): ?string
    {
        $name = trim((string) $name);

        return $name === '' ? null : Str::limit($name, 80, '');
    }
}
