<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Support;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verifies an OpenID Connect identity token against a provider's JWKS.
 *
 * Apple and Google both hand the app an RS256-signed ID token, so the
 * verification is the same shape for both and lives here once; what differs is
 * the issuer, the audiences and whether a nonce is expected, which the callers
 * supply.
 *
 * This is the trust boundary for "who is this person", so it fails closed on
 * every count:
 *
 * - **Algorithm is pinned to RS256.** Accepting whatever the token's header
 *   claims is the alg-confusion bug: a token with `"alg": "none"`, or one
 *   signed HS256 using the public key as an HMAC secret, would otherwise pass.
 *   `JWT::decode` is given the key set and the single permitted algorithm.
 * - **Audience and issuer are checked explicitly**, not assumed. A valid
 *   Google token minted for a *different* app is still a valid Google token.
 * - **The signing keys are fetched, cached and re-fetched on an unknown kid.**
 *   Providers rotate keys; a cached key set that has gone stale must not
 *   become an outage, and a `kid` we do not recognise is a cache-miss rather
 *   than a rejection.
 *
 * The signature maths itself is `firebase/php-jwt` rather than hand-rolled
 * openssl — see docs/DECISIONS.md ADR-022.
 */
final class OidcTokenVerifier
{
    /** Keys are long-lived but do rotate. An hour is Apple's own guidance. */
    private const JWKS_TTL_SECONDS = 3600;

    /**
     * @param  list<string>  $audiences  Any one of these is acceptable — Google
     *                                   issues per-platform client ids, so an
     *                                   iOS and an Android token differ here.
     * @param  list<string>  $issuers
     * @return array<string, mixed> The verified claims.
     *
     * @throws InvalidIdentityToken
     */
    public function verify(
        string $jwt,
        string $jwksUrl,
        array $audiences,
        array $issuers,
    ): array {
        if ($audiences === [] || $audiences === ['']) {
            // A misconfigured deploy must not accept everything. Without a
            // configured audience there is nothing to check against, and
            // "check nothing" is the one answer we never give.
            throw new InvalidIdentityToken('No audience is configured for this provider.');
        }

        $claims = $this->decode($jwt, $jwksUrl);

        $aud = $claims['aud'] ?? null;
        $iss = $claims['iss'] ?? null;

        if (! is_string($aud) || ! in_array($aud, $audiences, true)) {
            throw new InvalidIdentityToken('This token was issued for a different app.');
        }

        if (! is_string($iss) || ! in_array($iss, $issuers, true)) {
            throw new InvalidIdentityToken('This token did not come from the expected provider.');
        }

        if (! isset($claims['sub']) || ! is_string($claims['sub']) || $claims['sub'] === '') {
            throw new InvalidIdentityToken('This token identifies nobody.');
        }

        return $claims;
    }

    /**
     * Decode and check the signature, retrying once with fresh keys.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidIdentityToken
     */
    private function decode(string $jwt, string $jwksUrl): array
    {
        try {
            return $this->decodeWith($jwt, $this->keys($jwksUrl, fresh: false));
        } catch (InvalidIdentityToken $e) {
            throw $e;
        } catch (Throwable) {
            // Most likely an unknown `kid` because the provider rotated keys.
            // One retry with a fresh set, and if that fails the token is bad.
        }

        try {
            return $this->decodeWith($jwt, $this->keys($jwksUrl, fresh: true));
        } catch (Throwable $e) {
            // Deliberately vague to the caller: the reason a token failed is
            // not something an attacker gets to learn.
            throw new InvalidIdentityToken('That sign-in could not be verified.', previous: $e);
        }
    }

    /**
     * @param  array<string, Key>  $keys
     * @return array<string, mixed>
     */
    private function decodeWith(string $jwt, array $keys): array
    {
        // JWT::decode checks the signature, `exp` and `nbf`. The key set is
        // passed as a map, so only a `kid` the provider published can be used,
        // and the algorithm is fixed by the Key objects JWK::parseKeySet built.
        return (array) JWT::decode($jwt, $keys);
    }

    /**
     * The provider's published signing keys.
     *
     * @return array<string, Key>
     *
     * @throws InvalidIdentityToken
     */
    private function keys(string $jwksUrl, bool $fresh): array
    {
        $cacheKey = 'oidc-jwks:'.sha1($jwksUrl);

        if ($fresh) {
            Cache::forget($cacheKey);
        }

        /** @var array<string, mixed> $jwks */
        $jwks = Cache::remember($cacheKey, self::JWKS_TTL_SECONDS, function () use ($jwksUrl): array {
            $response = Http::timeout(5)->get($jwksUrl);

            if ($response->failed()) {
                throw new InvalidIdentityToken('Could not reach the sign-in provider.');
            }

            return $response->json();
        });

        // RS256 is asserted here, once, rather than trusted from each token's
        // own header — which is the whole point.
        return JWK::parseKeySet($jwks, 'RS256');
    }
}
