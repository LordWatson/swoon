<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Couple>
 */
class CoupleFactory extends Factory
{
    protected $model = Couple::class;

    /**
     * A couple record with no members yet — the state `AcceptInvite` is in
     * mid-transaction, and rarely what a test wants.
     *
     * Members are attached by an explicit state rather than by `configure()`,
     * because a `configure()` callback runs *before* the state callbacks: a
     * default pair would be attached first and then collide with the pair
     * `of()` asked for, on the unique (couple_id, role) index.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'linked_at' => now(),
            'lock_in_mode' => 'both_heart',
            'anniversary_on' => null,
            'first_date_on' => null,
            'unlinked_at' => null,
        ];
    }

    /**
     * A live couple with two fresh partners, in one call.
     *
     * Their colours differ, because the app requires it (rule 12) and a
     * factory producing an invalid couple would let a test pass on data the
     * app itself would reject.
     */
    public function linked(): static
    {
        return $this->afterCreating(function (Couple $couple): void {
            $this->attach($couple, User::factory()->create(['colour' => 'rose']), 'a');
            $this->attach($couple, User::factory()->create(['colour' => 'lavender']), 'b');
        });
    }

    /** A live couple of two specific people. */
    public function of(User $a, User $b): static
    {
        return $this->afterCreating(function (Couple $couple) use ($a, $b): void {
            $this->attach($couple, $a, 'a');
            $this->attach($couple, $b, 'b');
        });
    }

    /**
     * A couple that has already been unlinked — the state most of the privacy
     * rules are about, and the one easiest to forget to test.
     */
    public function unlinked(): static
    {
        return $this->linked()
            ->state(['unlinked_at' => now()])
            ->afterCreating(function (Couple $couple): void {
                $couple->members()->newPivotQuery()->update(['left_at' => now()]);
            });
    }

    private function attach(Couple $couple, User $user, string $role): void
    {
        $couple->members()->attach($user, [
            'role' => $role,
            'joined_at' => now(),
            'left_at' => null,
        ]);
    }
}
