<?php

declare(strict_types=1);

/*
 * Sign in with Apple and Google.
 *
 * Verified end to end with a real RSA keypair generated in the test and a
 * stubbed JWKS endpoint, so every case below exercises the actual signature
 * check — including the forged ones. Nothing here touches the network.
 *
 * The negative cases are the reason this file exists: a sign-in endpoint that
 * accepts a token it should not is a complete account takeover.
 */

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

usingDatabase();

const APPLE_JWKS = 'https://appleid.apple.com/auth/keys';
const GOOGLE_JWKS = 'https://www.googleapis.com/oauth2/v3/certs';

/**
 * A throwaway RSA keypair, plus the JWKS a provider would publish for it.
 *
 * Generated rather than committed: a PEM private key in the repository is a
 * thing to explain forever, even a test one. Memoised per name, because
 * 2048-bit generation is slow and most tests want the same key — `name` exists
 * so the forgery test can get a *different* key under the same `kid`.
 *
 * @return array{private: string, jwks: array<string, mixed>, kid: string}
 */
function keypair(string $name = 'provider', string $kid = 'test-key'): array
{
    static $cache = [];

    $memo = $name.'|'.$kid;

    if (isset($cache[$memo])) {
        return $cache[$memo];
    }

    $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ...opensslConfig(),
    ]);

    if ($resource === false) {
        throw new RuntimeException('openssl could not generate a key: '.openssl_error_string());
    }

    openssl_pkey_export($resource, $private, null, opensslConfig());
    $details = openssl_pkey_get_details($resource);

    $base64url = fn (string $bytes): string => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    return $cache[$memo] = [
        'private' => $private,
        'kid' => $kid,
        'jwks' => ['keys' => [[
            'kty' => 'RSA',
            'kid' => $kid,
            'use' => 'sig',
            'alg' => 'RS256',
            'n' => $base64url($details['rsa']['n']),
            'e' => $base64url($details['rsa']['e']),
        ]]],
    ];
}

/**
 * Where openssl's config lives, when it needs telling.
 *
 * Key *generation* reads openssl.cnf; signing and verifying do not. On Linux —
 * which is what CI runs — openssl finds it by itself and this returns nothing.
 * On Windows, PHP ships the file under `extras/ssl` and does not point at it,
 * so `openssl_pkey_new` fails with "configuration file routines::no such file"
 * unless it is named explicitly.
 *
 * @return array{config?: string}
 */
function opensslConfig(): array
{
    static $config = null;

    if ($config === null) {
        $config = [];

        $candidates = [
            getenv('OPENSSL_CONF') ?: null,
            dirname(PHP_BINARY).DIRECTORY_SEPARATOR.'extras'.DIRECTORY_SEPARATOR.'ssl'
                .DIRECTORY_SEPARATOR.'openssl.cnf',
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && is_file($candidate)) {
                $config = ['config' => $candidate];
                break;
            }
        }
    }

    return $config;
}

/**
 * @param  array{private: string, kid: string}  $key
 * @param  array<string, mixed>  $claims
 */
function signToken(array $key, array $claims): string
{
    return JWT::encode($claims, $key['private'], 'RS256', $key['kid']);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function appleClaims(array $overrides = []): array
{
    return array_merge([
        'iss' => 'https://appleid.apple.com',
        'aud' => 'test.swoon.app',
        'sub' => '000123.abcdef.0001',
        'email' => 'alex@example.com',
        'iat' => time(),
        'exp' => time() + 600,
    ], $overrides);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function googleClaims(array $overrides = []): array
{
    return array_merge([
        'iss' => 'https://accounts.google.com',
        'aud' => 'ios-client-id.apps.googleusercontent.com',
        'sub' => '110123456789',
        'email' => 'alex@example.com',
        'email_verified' => true,
        'name' => 'Alex Watson',
        'iat' => time(),
        'exp' => time() + 600,
    ], $overrides);
}

beforeEach(function (): void {
    config([
        'services.apple.client_id' => 'test.swoon.app',
        'services.google.client_id_ios' => 'ios-client-id.apps.googleusercontent.com',
        'services.google.client_id_android' => 'android-client-id.apps.googleusercontent.com',
        'services.google.client_id_web' => '',
    ]);

    Cache::flush();
});

describe('Sign in with Apple', function (): void {
    it('creates an account on first sign-in and returns our own token', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $response = $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims()),
            // Apple sends the name outside the token, once.
            'name' => 'Alex Watson',
        ])->assertOk()->assertJsonPath('user.name', 'Alex Watson');

        // What comes back is a Sanctum token of ours, never the provider's.
        expect($response->json('token'))->toBeString()
            ->and($response->json('token'))->not->toBe(signToken($key, appleClaims()));

        expect(User::where('apple_sub', '000123.abcdef.0001')->exists())->toBeTrue();
    });

    it('yields one user when the same person signs in twice', function (): void {
        // Keyed on the subject id, so a second sign-in finds the account
        // rather than making another.
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', ['identity_token' => signToken($key, appleClaims())])->assertOk();
        $this->postJson('/api/auth/apple', ['identity_token' => signToken($key, appleClaims())])->assertOk();

        expect(User::count())->toBe(1);
    });

    it('registers a private-relay address without treating it as an identity', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims([
                'email' => 'xyz123@privaterelay.appleid.com',
            ])),
        ])->assertOk();

        expect(User::where('email', 'xyz123@privaterelay.appleid.com')->exists())->toBeTrue();
    });

    it('never matches an existing account on a relay address', function (): void {
        // A relay address is specific to this app. Treating it as proof of
        // identity would let a token for one relay walk into an account that
        // happens to share it.
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        User::factory()->create(['email' => 'xyz123@privaterelay.appleid.com']);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims([
                'email' => 'xyz123@privaterelay.appleid.com',
                'sub' => 'someone.else.0002',
            ])),
        ])->assertOk();

        // A separate account, and with no email of its own — the address was
        // already taken, and `users.email` is unique. The account stands on
        // its subject id, which is what identifies it anyway.
        expect(User::count())->toBe(2)
            ->and(User::firstWhere('apple_sub', 'someone.else.0002')->email)->toBeNull();
    });

    it('copes with no name at all', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims()),
        ])->assertOk()->assertJsonPath('user.name', 'Someone lovely');
    });

    it('captures the real name later if it was missed the first time', function (): void {
        // Apple offers the name on first authorisation only, so a user who got
        // the placeholder must not be stuck with it for good.
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', ['identity_token' => signToken($key, appleClaims())])->assertOk();
        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims()),
            'name' => 'Alex Watson',
        ])->assertOk()->assertJsonPath('user.name', 'Alex Watson');
    });

    it('never overwrites a name the user chose themselves', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        User::factory()->create(['apple_sub' => '000123.abcdef.0001', 'name' => 'Al']);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims()),
            'name' => 'Alexander Watson',
        ])->assertOk()->assertJsonPath('user.name', 'Al');
    });
});

describe('a token we must not trust', function (): void {
    it('rejects one signed by the wrong key', function (): void {
        // The forgery case: a well-formed token with every claim right, signed
        // by a key the provider never published.
        $real = keypair();
        $forger = keypair('forger');

        Http::fake([APPLE_JWKS => Http::response($real['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($forger, appleClaims()),
        ])->assertStatus(422)->assertJsonValidationErrors('identity_token');

        expect(User::count())->toBe(0);
    });

    it('rejects an expired one', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims([
                'iat' => time() - 7200,
                'exp' => time() - 3600,
            ])),
        ])->assertStatus(422);
    });

    it('rejects one minted for a different app', function (): void {
        // A valid Google token for somebody else's app is still a valid Google
        // token. The audience check is the only thing that stops it.
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims([
                'aud' => 'some-other-app.apps.googleusercontent.com',
            ])),
        ])->assertStatus(422);
    });

    it('rejects one from the wrong issuer', function (): void {
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims(['iss' => 'https://evil.example.com'])),
        ])->assertStatus(422);
    });

    it('rejects a replayed nonce', function (): void {
        // The same token twice: valid signature, valid claims, already used.
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $nonce = Str::random(32);
        $token = signToken($key, appleClaims(['nonce' => $nonce]));

        $this->postJson('/api/auth/apple', [
            'identity_token' => $token,
            'nonce' => $nonce,
        ])->assertOk();

        $this->postJson('/api/auth/apple', [
            'identity_token' => $token,
            'nonce' => $nonce,
        ])->assertStatus(422);
    });

    it('rejects a nonce that does not match the one the app sent', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims(['nonce' => 'what-the-token-says'])),
            'nonce' => 'what-the-app-sent',
        ])->assertStatus(422);
    });

    it('refuses every token when no audience is configured', function (): void {
        // A deploy that forgot APPLE_CLIENT_ID must fail closed. With no
        // configured audience there is nothing to check, and "check nothing"
        // is the one answer we never give.
        config(['services.apple.client_id' => null]);

        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims()),
        ])->assertStatus(422);
    });

    it('says nothing about which check failed', function (): void {
        $key = keypair();
        Http::fake([APPLE_JWKS => Http::response($key['jwks'])]);

        $expired = $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims(['exp' => time() - 10])),
        ])->assertStatus(422);

        $wrongApp = $this->postJson('/api/auth/apple', [
            'identity_token' => signToken($key, appleClaims(['aud' => 'not.ours'])),
        ])->assertStatus(422);

        // Deliberately indistinguishable: which check failed helps someone
        // probing the endpoint and helps a real user not at all.
        expect($expired->json('message'))->toBe($wrongApp->json('message'));
    });
});

describe('Google sign-in', function (): void {
    it('accepts a token from any of our platform client ids', function (): void {
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims([
                'aud' => 'android-client-id.apps.googleusercontent.com',
                'sub' => '220987654321',
            ])),
        ])->assertOk();

        expect(User::where('google_sub', '220987654321')->exists())->toBeTrue();
    });

    it('ignores an unverified email rather than trusting it', function (): void {
        // An unverified address is not evidence of anything, so it must not be
        // used to match or to populate an account.
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims(['email_verified' => false])),
        ])->assertOk();

        expect(User::firstWhere('google_sub', '110123456789')->email)->toBeNull();
    });
});

describe('joining a social identity to an existing account', function (): void {
    it('stops and asks rather than linking on an email match', function (): void {
        // An email match is not proof of being the same person. Without this,
        // anyone who can get a provider to issue a token for an address could
        // walk into the account that already uses it.
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        User::factory()->create(['email' => 'alex@example.com']);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims()),
        ])->assertStatus(422)->assertJsonValidationErrors('link_confirmed');

        expect(User::whereNotNull('google_sub')->count())->toBe(0);
    });

    it('links once the person has confirmed it is theirs', function (): void {
        $key = keypair();
        Http::fake([GOOGLE_JWKS => Http::response($key['jwks'])]);

        $existing = User::factory()->create(['email' => 'alex@example.com']);

        $this->postJson('/api/auth/google', [
            'identity_token' => signToken($key, googleClaims()),
            'link_confirmed' => true,
        ])->assertOk()->assertJsonPath('user.id', $existing->id);

        expect(User::count())->toBe(1)
            ->and($existing->fresh()->google_sub)->toBe('110123456789');
    });
});
