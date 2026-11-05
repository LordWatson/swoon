<?php

declare(strict_types=1);

/*
 * The policies, tested from the outside in.
 *
 * Phase 10 makes a negative-authorisation test a standing requirement for
 * every endpoint. These are the model-level half of it: before any route
 * exists, prove that a stranger is refused, that an EX-partner is refused —
 * the case a "was this user ever a member" check would wrongly allow — and
 * that neither partner outranks the other.
 */

use App\Models\User;
use App\Modules\Accounts\Models\Couple;

usingDatabase();

describe('CouplePolicy', function (): void {
    it('lets either partner view, update and unlink', function (): void {
        // Both partners are equal: no owner, no admin, and no partner who can
        // overrule the other (rule 3).
        $couple = Couple::factory()->linked()->create();
        [$a, $b] = $couple->activeMembers()->get()->all();

        foreach ([$a, $b] as $partner) {
            expect($partner->can('view', $couple))->toBeTrue()
                ->and($partner->can('update', $couple))->toBeTrue()
                ->and($partner->can('unlink', $couple))->toBeTrue();
        }
    });

    it('refuses a stranger everything', function (): void {
        $couple = Couple::factory()->linked()->create();
        $stranger = User::factory()->create();

        expect($stranger->can('view', $couple))->toBeFalse()
            ->and($stranger->can('update', $couple))->toBeFalse()
            ->and($stranger->can('unlink', $couple))->toBeFalse();
    });

    it('refuses someone from another couple', function (): void {
        // The Phase 10 shape: two unrelated couples, and neither can see the
        // other's.
        $ours = Couple::factory()->linked()->create();
        $theirs = Couple::factory()->linked()->create();
        $other = $theirs->activeMembers()->first();

        expect($other->can('view', $ours))->toBeFalse();
    });

    it('refuses an ex-partner after the unlink', function (): void {
        // The one a careless check gets wrong. Membership is not history: once
        // someone has left, the couple's shared content is closed to them
        // (rule 4), and a policy asking "was this user ever a member" would
        // hand it straight back.
        $couple = Couple::factory()->unlinked()->create();
        $ex = $couple->members()->first();

        expect($ex->can('view', $couple))->toBeFalse()
            ->and($ex->can('update', $couple))->toBeFalse()
            ->and($ex->can('unlink', $couple))->toBeFalse();
    });

    it('cannot unlink a couple twice', function (): void {
        $couple = Couple::factory()->unlinked()->create();
        $ex = $couple->members()->first();

        expect($ex->can('unlink', $couple))->toBeFalse();
    });
});

describe('UserPolicy', function (): void {
    it('lets a user act on themselves and nobody else', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();

        expect($user->can('view', $user))->toBeTrue()
            ->and($user->can('update', $user))->toBeTrue()
            ->and($user->can('delete', $user))->toBeTrue()
            ->and($user->can('export', $user))->toBeTrue();

        expect($user->can('view', $other))->toBeFalse()
            ->and($user->can('update', $other))->toBeFalse()
            ->and($user->can('delete', $other))->toBeFalse()
            ->and($user->can('export', $other))->toBeFalse();
    });

    it('never lets a partner edit or delete the other account', function (): void {
        // A partner may look at profile basics. The account itself is theirs
        // alone — this is a coercive-control guard, not a convenience.
        $couple = Couple::factory()->linked()->create();
        [$a, $b] = $couple->activeMembers()->get()->all();

        expect($a->can('viewProfile', $b))->toBeTrue()
            ->and($a->can('update', $b))->toBeFalse()
            ->and($a->can('delete', $b))->toBeFalse()
            ->and($a->can('export', $b))->toBeFalse();
    });

    it('stops a stranger viewing a profile', function (): void {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        expect($stranger->can('viewProfile', $user))->toBeFalse();
    });

    it('closes profile access when the couple ends', function (): void {
        $couple = Couple::factory()->unlinked()->create();
        [$a, $b] = $couple->members()->get()->all();

        expect($a->can('viewProfile', $b))->toBeFalse();
    });
});
