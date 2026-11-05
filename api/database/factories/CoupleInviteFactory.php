<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Modules\Accounts\Models\CoupleInvite;
use App\Modules\Accounts\Support\InviteCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoupleInvite>
 */
class CoupleInviteFactory extends Factory
{
    protected $model = CoupleInvite::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inviter_user_id' => User::factory(),
            // The real generator, not fake()->word(): a factory that produced
            // codes the app would never issue would hide a bug in the alphabet.
            'code' => InviteCode::generate(),
            'email' => null,
            'expires_at' => now()->addDays((int) config('swoon.invites.expires_after_days')),
            'accepted_at' => null,
            'revoked_at' => null,
            'reminded_at' => null,
        ];
    }

    /** Past its 14 days, but never accepted or revoked. */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }

    public function accepted(?User $by = null): static
    {
        return $this->state([
            'accepted_at' => now(),
            'accepted_by_user_id' => ($by ?? User::factory())->getKey(),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }
}
