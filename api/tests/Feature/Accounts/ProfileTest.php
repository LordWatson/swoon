<?php

declare(strict_types=1);

/*
 * Profile updates and the partner nudge — the two endpoints onboarding needs.
 */

use App\Models\User;
use App\Modules\Accounts\Jobs\SendInviteEmail;
use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Support\Facades\Queue;

usingDatabase();

describe('updating your profile', function (): void {
    it('saves one answer at a time without wiping the others', function (): void {
        // Onboarding asks one question per screen (UX rule 3) and PATCHes each
        // answer as it comes, so a partial update must leave the rest alone.
        $user = User::factory()->create(['name' => 'Alex', 'timezone' => 'Europe/London']);

        $this->actingAs($user)
            ->patchJson('/api/me', ['dietary_needs' => ['vegetarian']])
            ->assertOk()
            ->assertJsonPath('dietary_needs', ['vegetarian'])
            ->assertJsonPath('name', 'Alex')
            ->assertJsonPath('timezone', 'Europe/London');

        $this->actingAs($user)
            ->patchJson('/api/me', ['timezone' => 'Australia/Sydney'])
            ->assertOk()
            ->assertJsonPath('timezone', 'Australia/Sydney')
            // Still there.
            ->assertJsonPath('dietary_needs', ['vegetarian']);
    });

    it('accepts an empty list as "no preference"', function (): void {
        // Every non-essential question needs a way to say nothing, and it must
        // be storable rather than silently skipped.
        $user = User::factory()->create(['dietary_needs' => ['vegan']]);

        $this->actingAs($user)
            ->patchJson('/api/me', ['dietary_needs' => []])
            ->assertOk()
            ->assertJsonPath('dietary_needs', []);
    });

    it('refuses a colour the theme has no token for', function (): void {
        $this->actingAs(User::factory()->create())
            ->patchJson('/api/me', ['colour' => 'neon'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('colour');
    });

    it('refuses a birthday in the future', function (): void {
        $this->actingAs(User::factory()->create())
            ->patchJson('/api/me', ['date_of_birth' => now()->addYear()->toDateString()])
            ->assertStatus(422);
    });

    it('refuses an invalid timezone', function (): void {
        $this->actingAs(User::factory()->create())
            ->patchJson('/api/me', ['timezone' => 'Mars/Olympus_Mons'])
            ->assertStatus(422);
    });

    it('cannot be used to set an email, a password or a social id', function (): void {
        // An email change needs re-verification and a password change needs
        // the old one, so neither is a field here. The social subject ids must
        // never be client-settable at all.
        $user = User::factory()->create(['email' => 'alex@example.com']);

        $this->actingAs($user)->patchJson('/api/me', [
            'email' => 'someone-else@example.com',
            'password' => 'brand-new-password',
            'apple_sub' => 'stolen-identity',
        ])->assertOk();

        $fresh = $user->fresh();

        expect($fresh->email)->toBe('alex@example.com')
            ->and($fresh->apple_sub)->toBeNull();
    });

    it('requires a token', function (): void {
        $this->patchJson('/api/me', ['name' => 'Nobody'])->assertUnauthorized();
    });
});

describe('nudging a partner who has not joined', function (): void {
    it('queues one reminder', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create([
            'inviter_user_id' => $user->getKey(),
            'email' => 'sam@example.com',
        ]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/remind')
            ->assertStatus(202);

        Queue::assertPushed(SendInviteEmail::class, 1);
    });

    it('enforces the cooldown on the server, not in the app', function (): void {
        // A client-side timer is advisory. The whole point of a cooldown is
        // that it cannot become pestering (UX rule 4).
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create([
            'inviter_user_id' => $user->getKey(),
            'email' => 'sam@example.com',
            'reminded_at' => now()->subHours(2),
        ]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/remind')
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');
    });

    it('allows another nudge once the cooldown has passed', function (): void {
        Queue::fake();

        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create([
            'inviter_user_id' => $user->getKey(),
            'email' => 'sam@example.com',
            'reminded_at' => now()->subHours((int) config('swoon.nudges.cooldown_hours') + 1),
        ]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/remind')
            ->assertStatus(202);
    });

    it('refuses when there is no address to nudge', function (): void {
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->create([
            'inviter_user_id' => $user->getKey(),
            'email' => null,
        ]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/remind')
            ->assertStatus(422);
    });

    it('refuses an invite that is no longer live', function (): void {
        $user = User::factory()->create();
        $invite = CoupleInvite::factory()->expired()->create([
            'inviter_user_id' => $user->getKey(),
            'email' => 'sam@example.com',
        ]);

        $this->actingAs($user)
            ->postJson('/api/invites/'.$invite->code.'/remind')
            ->assertStatus(422);
    });
});
