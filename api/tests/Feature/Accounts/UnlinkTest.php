<?php

declare(strict_types=1);

/*
 * Unlinking.
 *
 * The rule under test is a safety rule, not a feature: **either partner can
 * end the couple immediately, without the other's consent**. Requiring consent
 * — or even a heads-up first — would let a controlling partner trap someone in
 * a shared space they cannot leave. So the cases here are about there being no
 * pending state, no grace period, and no way for the other side to object.
 */

use App\Models\User;
use App\Modules\Accounts\Events\CoupleUnlinked;
use App\Modules\Accounts\Jobs\SendUnlinkNotice;
use App\Modules\Accounts\Mail\UnlinkNoticeMail;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

usingDatabase();

describe('unlinking', function (): void {
    it('takes effect on the same request, from either side', function (): void {
        foreach (['a', 'b'] as $side) {
            $couple = Couple::factory()->linked()->create();
            $actor = $couple->members()->wherePivot('role', $side)->first();

            $this->actingAs($actor)->deleteJson('/api/couple')->assertNoContent();

            // Not pending, not scheduled: done.
            expect($couple->fresh()->isLinked())->toBeFalse()
                ->and($actor->fresh()->isCoupled())->toBeFalse();
        }
    });

    it('needs no consent and gives the other partner nothing to object to', function (): void {
        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        // The other partner is simply out, with no say in it and no state
        // they could reverse.
        expect($other->fresh()->isCoupled())->toBeFalse()
            ->and($couple->fresh()->activeMembers()->count())->toBe(0);
    });

    it('closes the couple rather than deleting it', function (): void {
        // Rule 4: detach, do not delete. The shared content stays with the
        // closed couple; it does not vanish and it does not follow anyone out.
        $couple = Couple::factory()->linked()->create();
        $actor = $couple->activeMembers()->first();

        $this->actingAs($actor)->deleteJson('/api/couple')->assertNoContent();

        expect(Couple::whereKey($couple->getKey())->exists())->toBeTrue()
            ->and($couple->fresh()->unlinked_at)->not->toBeNull()
            // Both memberships are kept, ended — so "who authored this" still
            // resolves for everything either of them added.
            ->and($couple->fresh()->members()->count())->toBe(2);
    });

    it('shuts both ex-partners out of the couple', function (): void {
        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        // Neither can read it, and neither can see the other any more.
        expect($leaver->fresh()->can('view', $couple->fresh()))->toBeFalse()
            ->and($other->fresh()->can('view', $couple->fresh()))->toBeFalse()
            ->and($leaver->fresh()->partner())->toBeNull()
            ->and($other->fresh()->partner())->toBeNull();

        $this->actingAs($leaver->fresh())->getJson('/api/couple')->assertNotFound();
        $this->actingAs($other->fresh())->getJson('/api/couple')->assertNotFound();
    });

    it('frees both partners to link again', function (): void {
        // The invariant releases when the memberships end, so a new invite is
        // immediately possible — leaving must not be a one-way door out of the
        // product.
        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        $this->actingAs($leaver->fresh())->postJson('/api/invites')->assertCreated();

        $invite = CoupleInvite::factory()->create();
        $this->actingAs($other->fresh())
            ->postJson('/api/invites/'.$invite->code.'/accept')
            ->assertCreated();
    });

    it('cannot be done twice', function (): void {
        $couple = Couple::factory()->unlinked()->create();
        $ex = $couple->members()->first();

        // There is no couple to find any more, so this is a 404 rather than a
        // second no-op unlink.
        $this->actingAs($ex)->deleteJson('/api/couple')->assertNotFound();
    });

    it('refuses a stranger and an unauthenticated caller', function (): void {
        Couple::factory()->linked()->create();

        $this->deleteJson('/api/couple')->assertUnauthorized();
        // A solo user has no couple of their own, and cannot reach anyone
        // else's: there is no id in the route to reach it with.
        $this->actingAs(User::factory()->create())->deleteJson('/api/couple')->assertNotFound();
    });

    it('fires CoupleUnlinked once, naming who left', function (): void {
        Event::fake([CoupleUnlinked::class]);

        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        Event::assertDispatchedTimes(CoupleUnlinked::class, 1);
        Event::assertDispatched(
            CoupleUnlinked::class,
            fn (CoupleUnlinked $e): bool => $e->unlinkedBy->is($leaver)
                && $e->otherPartner?->is($other) === true,
        );
    });
});

describe('the notice to the other partner', function (): void {
    it('goes to the other partner, not the one who left', function (): void {
        Queue::fake();

        $couple = Couple::factory()->linked()->create();
        [$leaver, $other] = $couple->activeMembers()->get()->all();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        Queue::assertPushed(SendUnlinkNotice::class, 1);
    });

    it('still unlinks when the other partner has no email to write to', function (): void {
        // A social-only Apple account may have none. The unlink must not fail
        // because the notice cannot be sent.
        $leaver = User::factory()->create();
        $other = User::factory()->create(['email' => null]);
        Couple::factory()->of($leaver, $other)->create();

        Queue::fake();

        $this->actingAs($leaver)->deleteJson('/api/couple')->assertNoContent();

        Queue::assertNothingPushed();
        expect($leaver->fresh()->isCoupled())->toBeFalse();
    });

    it('says what happened, plainly, and nothing else', function (): void {
        // This email may be read by someone in a bad situation. No reason, no
        // blame, no "get in touch", no undo, and nothing in the future tense —
        // anything that invites a negotiation makes the safety feature worse
        // (rule 5).
        $rendered = (new UnlinkNoticeMail('Sam'))->render();

        expect($rendered)->toContain('has closed')
            ->toContain('Sam')
            ->toContain('still yours');

        foreach (['why', 'reason', 'undo', 'restore', 'get in touch', 'contact', 'will be', 'about to'] as $forbidden) {
            expect(strtolower($rendered))->not->toContain($forbidden);
        }
    });

    it('is sent without needing the account to still exist', function (): void {
        // "Unlink, then delete my account" is exactly what someone leaving
        // does, so the job carries strings rather than a User.
        Mail::fake();

        (new SendUnlinkNotice('sam@example.com', 'Sam'))->handle();

        Mail::assertSent(UnlinkNoticeMail::class);
    });
});

describe('couple settings', function (): void {
    it('lets either partner change them', function (): void {
        $couple = Couple::factory()->linked()->create();

        foreach ($couple->activeMembers()->get() as $partner) {
            $this->actingAs($partner)
                ->patchJson('/api/couple', ['lock_in_mode' => 'either_locks'])
                ->assertOk()
                ->assertJsonPath('lock_in_mode', 'either_locks');
        }
    });

    it('refuses a future anniversary', function (): void {
        $couple = Couple::factory()->linked()->create();

        $this->actingAs($couple->activeMembers()->first())
            ->patchJson('/api/couple', ['anniversary_on' => now()->addYear()->toDateString()])
            ->assertStatus(422);
    });

    it('404s for a solo user', function (): void {
        $this->actingAs(User::factory()->create())->getJson('/api/couple')->assertNotFound();
    });
});
