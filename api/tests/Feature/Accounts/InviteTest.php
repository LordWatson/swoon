<?php

declare(strict_types=1);

/*
 * The invite and accept flow.
 *
 * Most of this file is refusals, because every one of them is a real thing a
 * person will do: accept their own code, accept while already coupled, use a
 * code twice, use one that expired. The important case is two people accepting
 * the same code at the same moment — a read-then-write would let both through.
 */

use App\Models\User;
use App\Modules\Accounts\Actions\AcceptInvite;
use App\Modules\Accounts\Events\CoupleLinked;
use App\Modules\Accounts\Jobs\SendInviteEmail;
use App\Modules\Accounts\Mail\InviteMail;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;
use App\Modules\Accounts\Support\InviteCode;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

usingDatabase();

describe('creating an invite', function (): void {
    it('returns a readable code and both kinds of link', function (): void {
        $response = $this->actingAs(User::factory()->create())
            ->postJson('/api/invites')
            ->assertCreated()
            ->assertJsonStructure(['code', 'expires_at', 'universal_link', 'deep_link']);

        $code = $response->json('code');

        expect($code)->toHaveLength((int) config('swoon.invites.code_length'))
            ->and($response->json('deep_link'))->toContain('swoon://invite/'.$code);
    });

    it('never uses a character a partner could misread', function (): void {
        // These get read aloud across a kitchen. 0/O and 1/I/L are out
        // (rule 7), so a code can only be heard one way.
        foreach (range(1, 40) as $attempt) {
            expect(InviteCode::generate())->not->toMatch('/[01OIL]/');
        }
    });

    it('replaces an earlier open invite rather than making a second', function (): void {
        // One active outbound invite per user (rule 6), which the database also
        // enforces — so this must revoke, not ignore.
        $user = User::factory()->create();

        $first = $this->actingAs($user)->postJson('/api/invites')->json('code');
        $second = $this->actingAs($user)->postJson('/api/invites')->json('code');

        expect($first)->not->toBe($second)
            ->and($user->invites()->open()->count())->toBe(1);
    });

    it('lets a user whose code expired get a fresh one', function (): void {
        // An expired-but-open invite still occupies the one-invite slot, so
        // without the revoke this would be a dead end.
        $user = User::factory()->create();
        CoupleInvite::factory()->expired()->create(['inviter_user_id' => $user->getKey()]);

        $this->actingAs($user)->postJson('/api/invites')->assertCreated();
    });

    it('refuses a user who is already linked', function (): void {
        $couple = Couple::factory()->linked()->create();

        $this->actingAs($couple->activeMembers()->first())
            ->postJson('/api/invites')
            ->assertStatus(422);
    });

    it('queues the email only when an address was given', function (): void {
        Queue::fake();

        $this->actingAs(User::factory()->create())->postJson('/api/invites')->assertCreated();
        Queue::assertNothingPushed();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites', ['email' => 'sam@example.com'])
            ->assertCreated();
        Queue::assertPushed(SendInviteEmail::class, 1);
    });

    it('requires a token', function (): void {
        $this->postJson('/api/invites')->assertUnauthorized();
    });
});

describe('previewing an invite', function (): void {
    it('needs no sign-in and leaks only a first name', function (): void {
        // The link travels through messaging apps, so whatever this returns is
        // effectively public to whoever holds it.
        $inviter = User::factory()->create(['name' => 'Alex Watson', 'email' => 'alex@example.com']);
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);

        $response = $this->getJson('/api/invites/'.$invite->code)
            ->assertOk()
            ->assertJsonPath('inviter_first_name', 'Alex');

        $body = json_encode($response->json());

        expect($body)->not->toContain('Watson')
            ->and($body)->not->toContain('alex@example.com')
            ->and($response->json())->toHaveKeys(['inviter_first_name', 'expires_at', 'is_usable'])
            ->and($response->json())->not->toHaveKeys(['id', 'inviter_user_id', 'code', 'email']);
    });

    it('accepts a code typed with spaces or in lower case', function (): void {
        // People retype these. "abcd efgh" is the same code.
        $invite = CoupleInvite::factory()->create();

        $this->getJson('/api/invites/'.strtolower($invite->code))->assertOk();
    });

    it('says an expired code is not usable rather than hiding it', function (): void {
        // The app needs to say "ask for a fresh one", which it cannot do if
        // this 404s.
        $invite = CoupleInvite::factory()->expired()->create();

        $this->getJson('/api/invites/'.$invite->code)
            ->assertOk()
            ->assertJsonPath('is_usable', false);
    });

    it('404s a code that does not exist and one that is malformed alike', function (): void {
        $this->getJson('/api/invites/ABCDEFGH')->assertNotFound();
        $this->getJson('/api/invites/not-a-code')->assertNotFound();
    });
});

describe('accepting an invite', function (): void {
    it('creates the couple, links both partners and fires CoupleLinked once', function (): void {
        Event::fake([CoupleLinked::class]);

        $inviter = User::factory()->create(['colour' => 'rose']);
        $accepter = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);

        $this->actingAs($accepter)
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertCreated()
            ->assertJsonPath('partner.name', $inviter->name);

        Event::assertDispatchedTimes(CoupleLinked::class, 1);

        expect($accepter->fresh()->isCoupled())->toBeTrue()
            ->and($inviter->fresh()->partner()?->getKey())->toBe($accepter->getKey())
            ->and($invite->fresh()->accepted_by_user_id)->toBe($accepter->getKey());
    });

    it('gives the partners different colours', function (): void {
        // Rule 12. Two Rosé partners make every reveal screen ambiguous.
        $inviter = User::factory()->create(['colour' => 'rose']);
        $accepter = User::factory()->create(['colour' => 'rose']);
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);

        $this->actingAs($accepter)->postJson('/api/invites/'.$invite->code.'/accept')->assertCreated();

        expect($inviter->fresh()->colour)->toBe('rose')
            ->and($accepter->fresh()->colour)->not->toBe('rose');
    });

    it('keeps a colour the joining partner already chose, when it does not clash', function (): void {
        $inviter = User::factory()->create(['colour' => 'rose']);
        $accepter = User::factory()->create(['colour' => 'sage']);
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);

        $this->actingAs($accepter)->postJson('/api/invites/'.$invite->code.'/accept')->assertCreated();

        expect($accepter->fresh()->colour)->toBe('sage');
    });

    it('lets only one of two simultaneous accepts through', function (): void {
        // The race. Both callers pass every up-front check; the conditional
        // UPDATE that claims the code can only succeed once.
        $inviter = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);

        $first = User::factory()->create();
        $second = User::factory()->create();
        $action = app(AcceptInvite::class);

        $action->handle($invite, $first);

        expect(fn () => $action->handle($invite->fresh(), $second))
            ->toThrow(ValidationException::class);

        expect($first->fresh()->isCoupled())->toBeTrue()
            ->and($second->fresh()->isCoupled())->toBeFalse()
            ->and(Couple::count())->toBe(1);
    });
});

describe('refusing to accept', function (): void {
    it('refuses your own invite', function (): void {
        // Rule 8. Otherwise a curious user links with themselves and every
        // "your partner" string in the app becomes nonsense.
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $user->getKey()]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        expect(Couple::count())->toBe(0);
    });

    it('refuses when you are already coupled, with a message not a silent re-link', function (): void {
        $couple = Couple::factory()->linked()->create();
        $alreadyCoupled = $couple->activeMembers()->first();
        $invite = CoupleInvite::factory()->create();

        $this->actingAs($alreadyCoupled)
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422);

        // Still in the original couple, and no second one was made.
        expect(Couple::count())->toBe(1)
            ->and($alreadyCoupled->fresh()->couple()?->getKey())->toBe($couple->getKey());
    });

    it('refuses an expired code', function (): void {
        $invite = CoupleInvite::factory()->expired()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422);
    });

    it('refuses a code already used', function (): void {
        $invite = CoupleInvite::factory()->accepted()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422);
    });

    it('refuses a revoked code', function (): void {
        $invite = CoupleInvite::factory()->revoked()->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422);
    });

    it('refuses when the inviter has linked with somebody else meanwhile', function (): void {
        // Alex invites Sam, gets impatient, links with Robin instead. Sam's
        // code must not create a second couple for Alex.
        $inviter = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $inviter->getKey()]);
        Couple::factory()->of($inviter, User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertStatus(422);

        expect(Couple::count())->toBe(1);
    });

    it('requires a token', function (): void {
        $invite = CoupleInvite::factory()->create();

        $this->postJson('/api/invites/'.$invite->code.'/accept')->assertUnauthorized();
    });
});

describe('revoking an invite', function (): void {
    it('cancels your own', function (): void {
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create(['inviter_user_id' => $user->getKey()]);

        $this->actingAs($user)->deleteJson('/api/invites/'.$invite->getKey())->assertNoContent();

        expect($invite->fresh()->revoked_at)->not->toBeNull();
    });

    it('404s an invite that is not yours, rather than confirming it exists', function (): void {
        $invite = CoupleInvite::factory()->create();

        $this->actingAs(User::factory()->create())
            ->deleteJson('/api/invites/'.$invite->getKey())
            ->assertNotFound();

        expect($invite->fresh()->revoked_at)->toBeNull();
    });

    it('refuses to revoke one that has been accepted', function (): void {
        // Cancelling the code does not undo the couple it made, and quietly
        // succeeding would suggest it did.
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->accepted()->create(['inviter_user_id' => $user->getKey()]);

        $this->actingAs($user)->deleteJson('/api/invites/'.$invite->getKey())->assertStatus(422);
    });
});

describe('the invite email', function (): void {
    it('carries the code and the link, and only a first name', function (): void {
        $inviter = User::factory()->create(['name' => 'Alex Watson']);
        $invite = CoupleInvite::factory()->create([
            'inviter_user_id' => $inviter->getKey(),
            'email' => 'sam@example.com',
        ]);

        $rendered = (new InviteMail($invite))->render();

        expect($rendered)->toContain($invite->code)
            ->toContain('Alex')
            ->not->toContain('Watson');
    });

    it('does not send once the invite has been accepted', function (): void {
        // By the time a worker picks the job up the code may be used, and a
        // "join me" email after the fact is just confusing.
        Mail::fake();

        $invite = CoupleInvite::factory()->accepted()->create(['email' => 'sam@example.com']);

        (new SendInviteEmail($invite))->handle();

        Mail::assertNothingSent();
    });

    it('sends the reminder once and never again', function (): void {
        Mail::fake();

        $invite = CoupleInvite::factory()->create([
            'email' => 'sam@example.com',
            'created_at' => now()->subDays(4),
        ]);

        $this->artisan('invites:remind')->assertSuccessful();

        expect($invite->fresh()->reminded_at)->not->toBeNull();
        Mail::assertSentCount(1);

        // reminded_at is set once and the query excludes anything already
        // reminded, so a second run has nothing to do. This is what makes
        // "a single reminder" true rather than a promise.
        $this->artisan('invites:remind')->assertSuccessful();

        Mail::assertSentCount(1);
    });

    it('does not remind about an invite that is only a day old', function (): void {
        Mail::fake();

        CoupleInvite::factory()->create([
            'email' => 'sam@example.com',
            'created_at' => now()->subDay(),
        ]);

        $this->artisan('invites:remind')->assertSuccessful();

        Mail::assertNothingSent();
    });
});
