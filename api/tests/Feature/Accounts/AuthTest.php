<?php

declare(strict_types=1);

/*
 * Sign up, sign in, sign out.
 *
 * The cases worth the most here are the negative ones: that a wrong address
 * and a wrong password are indistinguishable, that signing out on one device
 * leaves the others signed in, and that /api/me is useless without a token.
 */

use App\Models\User;
use App\Modules\Accounts\Models\Couple;

usingDatabase();

describe('registration', function (): void {
    it('creates a solo user and returns a working token', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'a-reasonable-passphrase',
        ])->assertCreated()->assertJsonPath('user.email', 'alex@example.com');

        $token = $response->json('token');

        expect($token)->toBeString();

        // A new user is solo: a couple exists only once an invite is accepted.
        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.name', 'Alex')
            ->assertJsonPath('couple', null);
    });

    it('hashes the password', function (): void {
        $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'a-reasonable-passphrase',
        ])->assertCreated();

        expect(User::firstWhere('email', 'alex@example.com')->password)
            ->not->toBe('a-reasonable-passphrase');
    });

    it('never returns the password or the social ids', function (): void {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'a-reasonable-passphrase',
        ])->assertCreated();

        expect($response->json('user'))
            ->not->toHaveKeys(['password', 'apple_sub', 'google_sub']);
    });

    it('rejects a duplicate email', function (): void {
        User::factory()->create(['email' => 'alex@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'a-reasonable-passphrase',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    });

    it('rejects a short password', function (): void {
        $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'short',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    });

    it('defaults the timezone and accepts one that is offered', function (): void {
        $this->postJson('/api/auth/register', [
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'password' => 'a-reasonable-passphrase',
            'timezone' => 'Australia/Sydney',
        ])->assertCreated()->assertJsonPath('user.timezone', 'Australia/Sydney');

        $this->postJson('/api/auth/register', [
            'name' => 'Sam',
            'email' => 'sam@example.com',
            'password' => 'a-reasonable-passphrase',
        ])->assertCreated()->assertJsonPath('user.timezone', config('app.timezone'));
    });
});

describe('sign-in', function (): void {
    it('returns a token for the right password', function (): void {
        User::factory()->create(['email' => 'alex@example.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'password',
        ])->assertOk()->assertJsonPath('user.email', 'alex@example.com')
            ->assertJsonStructure(['user', 'token']);
    });

    it('fails identically for a wrong password and an unknown address', function (): void {
        // The enumeration guard, asserted rather than trusted: if these two
        // responses ever differ, the endpoint becomes a way to find out who
        // has an account.
        User::factory()->create(['email' => 'alex@example.com']);

        $wrongPassword = $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(422);

        $noSuchUser = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(422);

        expect($noSuchUser->json())->toEqual($wrongPassword->json());
    });

    it('says nothing about whether the account exists', function (): void {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'not-the-password',
        ])->assertStatus(422);

        $body = json_encode($response->json());

        expect($body)->not->toContain('no such')
            ->and($body)->not->toContain('not found')
            ->and($body)->not->toContain('password is');
    });

    it('refuses a social-only account with no password set', function (): void {
        // There is nothing to check against, so it must fail like any other
        // wrong password — not throw on a null hash.
        User::factory()->create(['email' => 'apple@example.com', 'password' => null]);

        $this->postJson('/api/auth/login', [
            'email' => 'apple@example.com',
            'password' => 'anything-at-all',
        ])->assertStatus(422);
    });
});

describe('sign-out', function (): void {
    it('revokes this device only, leaving other devices signed in', function (): void {
        // Signing out on a phone must not sign you out on your tablet.
        $user = User::factory()->create();
        $phone = $user->createToken('phone')->plainTextToken;
        $tablet = $user->createToken('tablet')->plainTextToken;

        $this->withToken($phone)->postJson('/api/auth/logout')->assertNoContent();

        // The tablet's token is the one still standing.
        expect($user->tokens()->pluck('name')->all())->toBe(['tablet']);

        // Within one test the auth guard keeps the user it already resolved,
        // so the revoked token would still appear to work. A real request is a
        // fresh process; forgetting the guards is what makes these two calls
        // behave like two requests rather than one.
        app('auth')->forgetGuards();
        $this->withToken($phone)->getJson('/api/me')->assertUnauthorized();

        app('auth')->forgetGuards();
        $this->withToken($tablet)->getJson('/api/me')->assertOk();
    });
});

describe('/api/me', function (): void {
    it('requires a token', function (): void {
        $this->getJson('/api/me')->assertUnauthorized();
    });

    it('rejects a token that has been revoked', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('phone')->plainTextToken;
        $user->tokens()->delete();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    });

    it('carries the couple summary and the partner once linked', function (): void {
        $alex = User::factory()->create(['colour' => 'rose']);
        $sam = User::factory()->create(['name' => 'Sam', 'colour' => 'lavender']);
        $couple = Couple::factory()->of($alex, $sam)->create();

        $this->actingAs($alex)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('couple.id', $couple->fresh()->ulid)
            ->assertJsonPath('couple.partner.name', 'Sam')
            ->assertJsonPath('couple.partner.colour', 'lavender');
    });

    it('never leaks the partner email address', function (): void {
        // A partner sees profile basics. The address is shown only as the
        // masked form the inviter typed, never in a payload.
        $alex = User::factory()->create();
        $sam = User::factory()->create(['email' => 'sam@example.com']);
        Couple::factory()->of($alex, $sam)->create();

        $response = $this->actingAs($alex)->getJson('/api/me')->assertOk();

        expect($response->json('couple.partner'))->not->toHaveKey('email')
            ->and(json_encode($response->json()))->not->toContain('sam@example.com');
    });

    it('carries an entitlement the app can gate on from day one', function (): void {
        $this->actingAs(User::factory()->create())->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('entitlement.tier', 'spark')
            ->assertJsonPath('entitlement.is_paid', false);
    });
});

describe('rate limiting', function (): void {
    it('throttles repeated failures for one address', function (): void {
        User::factory()->create(['email' => 'alex@example.com']);

        // Five wrong attempts are allowed; the sixth is refused.
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/auth/login', [
                'email' => 'alex@example.com',
                'password' => 'wrong',
            ])->assertStatus(422);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'alex@example.com',
            'password' => 'wrong',
        ])->assertStatus(429);
    });

    it('does not let a stranger lock a real person out', function (): void {
        // The reason the email limit is keyed on email+IP, not email alone:
        // an attacker who burns the limit from their own address must not stop
        // the account's owner signing in from theirs.
        User::factory()->create(['email' => 'alex@example.com']);

        foreach (range(1, 6) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
                ->postJson('/api/auth/login', [
                    'email' => 'alex@example.com',
                    'password' => 'wrong',
                ]);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.4'])
            ->postJson('/api/auth/login', [
                'email' => 'alex@example.com',
                'password' => 'password',
            ])->assertOk();
    });
});
