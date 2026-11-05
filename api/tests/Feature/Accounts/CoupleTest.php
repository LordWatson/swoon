<?php

declare(strict_types=1);

/*
 * The couple, and the one invariant the whole product rests on.
 *
 * "One active couple per user" cannot be enforced in PHP alone: two
 * simultaneous accepts would both pass a check-then-write. So it is a unique
 * index on a generated column, and the test that matters is the one that goes
 * round the application entirely and inserts straight into the table.
 */

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

usingDatabase();

describe('the one-couple invariant', function (): void {
    it('is enforced by the database, not by the application', function (): void {
        $user = User::factory()->create();
        $first = Couple::factory()->create();
        $second = Couple::factory()->create();

        $first->members()->attach($user, ['role' => 'a', 'joined_at' => now()]);

        // Straight to the table: no model events, no Action, no validation.
        // If this succeeds, the invariant is a comment rather than a rule.
        expect(fn () => DB::table('couple_user')->insert([
            'couple_id' => $second->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'b',
            'joined_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(QueryException::class);
    });

    it('survives two concurrent accepts of two different invites', function (): void {
        // The race the invariant exists for: both transactions read "not
        // coupled", both write. One must lose.
        $user = User::factory()->create();
        $a = Couple::factory()->create();
        $b = Couple::factory()->create();

        $attach = fn (Couple $couple, string $role) => DB::transaction(
            fn () => $couple->members()->attach($user, ['role' => $role, 'joined_at' => now()])
        );

        $attach($a, 'a');

        expect(fn () => $attach($b, 'b'))->toThrow(QueryException::class)
            ->and($user->couples()->wherePivotNull('left_at')->count())->toBe(1);
    });

    it('frees the user to link again once they have left', function (): void {
        $user = User::factory()->create();
        $old = Couple::factory()->create();
        $new = Couple::factory()->create();

        $old->members()->attach($user, ['role' => 'a', 'joined_at' => now()]);
        $old->members()->updateExistingPivot($user->getKey(), ['left_at' => now()]);

        $new->members()->attach($user, ['role' => 'a', 'joined_at' => now()]);

        expect($user->couple()?->getKey())->toBe($new->getKey());
    });

    it('cannot hold a third member', function (): void {
        $couple = Couple::factory()->linked()->create();
        $third = User::factory()->create();

        // Only 'a' and 'b' exist, and both are taken — so a third member has
        // no role left to take.
        expect(fn () => $couple->members()->attach($third, ['role' => 'a', 'joined_at' => now()]))
            ->toThrow(QueryException::class);
    });
});

describe('a couple', function (): void {
    it('is built with two partners of different colours in one call', function (): void {
        $couple = Couple::factory()->linked()->create();

        $colours = $couple->activeMembers()->pluck('colour');

        expect($couple->activeMembers()->count())->toBe(2)
            ->and($colours->unique()->count())->toBe(2);
    });

    it('allocates a public ULID without being asked', function (): void {
        // Ids in payloads are ULIDs so a couple's existence cannot be inferred
        // by counting. Allocated in the model, so no code path can skip it.
        $couple = Couple::factory()->create();

        expect($couple->ulid)->toBeString()->toHaveLength(26);
    });

    it('tells each partner who the other one is', function (): void {
        $couple = Couple::factory()->linked()->create();
        [$a, $b] = $couple->activeMembers()->get()->all();

        expect($couple->partnerFor($a)->getKey())->toBe($b->getKey())
            ->and($couple->partnerFor($b)->getKey())->toBe($a->getKey());
    });

    it('has no partners once it is unlinked', function (): void {
        // The privacy rule in model form: after unlinking, neither ex-partner
        // resolves through the couple any more (rule 4).
        $couple = Couple::factory()->unlinked()->create();
        $someone = $couple->members()->first();

        expect($couple->isLinked())->toBeFalse()
            ->and($couple->partnerFor($someone))->toBeNull()
            ->and($couple->activeMembers()->count())->toBe(0);
    });
});

describe('a user', function (): void {
    it('is solo by default', function (): void {
        $user = User::factory()->create();

        expect($user->couple())->toBeNull()
            ->and($user->isCoupled())->toBeFalse()
            ->and($user->partner())->toBeNull();
    });

    it('resolves its couple and its partner once linked', function (): void {
        $alex = User::factory()->create();
        $sam = User::factory()->create();
        $couple = Couple::factory()->of($alex, $sam)->create();

        expect($alex->couple()?->getKey())->toBe($couple->getKey())
            ->and($alex->isCoupled())->toBeTrue()
            ->and($alex->partner()?->getKey())->toBe($sam->getKey());
    });

    it('goes back to solo after the couple is unlinked', function (): void {
        $couple = Couple::factory()->unlinked()->create();
        $user = $couple->members()->first();

        expect($user->couple())->toBeNull()
            ->and($user->isCoupled())->toBeFalse()
            ->and($user->partner())->toBeNull();
    });
});
