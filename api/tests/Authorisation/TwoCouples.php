<?php

declare(strict_types=1);

namespace Tests\Authorisation;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Accounts\Models\CoupleInvite;

/**
 * Two unrelated couples and a stranger — the fixture every authorisation
 * sweep needs.
 *
 * Phase 10 makes "every endpoint has a negative-authorisation test" a standing
 * requirement, and this is the one line each later phase copies:
 *
 *     $world = TwoCouples::make();
 *     $this->actingAs($world->outsider)->getJson('/api/moments/'.$id)->assertNotFound();
 *
 * `outsider` is the one to reach for. It is a member of a **real, live couple**
 * rather than a solo user, because a solo user is refused by accident — they
 * have no couple, so half the routes 404 for them whatever the authorisation
 * says. An outsider who is fully set up, just not *this* couple, is the case
 * that actually tests the rule.
 */
final readonly class TwoCouples
{
    private function __construct(
        public Couple $couple,
        public User $alex,
        public User $sam,
        public Couple $otherCouple,
        /** A member of the other couple: properly set up, and none of your business. */
        public User $outsider,
        public User $outsidersPartner,
        /** Signed in, no couple at all. */
        public User $solo,
        /** Alex's outstanding invite, for routes that take one. */
        public CoupleInvite $invite,
    ) {}

    public static function make(): self
    {
        $alex = User::factory()->create(['name' => 'Alex Watson', 'colour' => 'rose']);
        $sam = User::factory()->create(['name' => 'Sam Hart', 'colour' => 'lavender']);
        $couple = Couple::factory()->of($alex, $sam)->create();

        $robin = User::factory()->create(['name' => 'Robin Fell', 'colour' => 'rose']);
        $kit = User::factory()->create(['name' => 'Kit Moss', 'colour' => 'sage']);
        $otherCouple = Couple::factory()->of($robin, $kit)->create();

        // Belongs to nobody in either couple, so it can be used to prove an
        // invite id is not a way in.
        $solo = User::factory()->create(['name' => 'Nobody Here']);

        return new self(
            couple: $couple->fresh(),
            alex: $alex->fresh(),
            sam: $sam->fresh(),
            otherCouple: $otherCouple->fresh(),
            outsider: $robin->fresh(),
            outsidersPartner: $kit->fresh(),
            solo: $solo,
            invite: CoupleInvite::factory()->create(['inviter_user_id' => $solo->getKey()]),
        );
    }
}
