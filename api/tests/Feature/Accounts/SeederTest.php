<?php

declare(strict_types=1);

/*
 * The local-development seeder.
 *
 * Worth testing because local work depends on it and it fails *silently*: if
 * the factory's default password changes, or the colour rule tightens, or the
 * one-couple invariant rejects a second run, the symptom is a founder who
 * cannot sign in and has no idea why. These assertions are cheap and the
 * failure mode they prevent is an afternoon.
 */

use App\Models\User;
use Database\Seeders\DatabaseSeeder;

usingDatabase();

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('seeds a couple who can both sign in', function (): void {
    // Through the real endpoint: "the row exists" is not the same claim as
    // "I can sign in", and the second is the one that matters.
    foreach (['alex@user.com', 'rimma@user.com'] as $email) {
        $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => DatabaseSeeder::PASSWORD,
        ])->assertOk()->assertJsonStructure(['user', 'token']);
    }
});

it('links them to each other, so signing in lands on the tabs', function (): void {
    $alex = User::firstWhere('email', 'alex@user.com');
    $rimma = User::firstWhere('email', 'rimma@user.com');

    expect($alex->partner()?->getKey())->toBe($rimma->getKey())
        ->and($rimma->partner()?->getKey())->toBe($alex->getKey());

    // `couple` non-null is what the app routes on, so assert the payload and
    // not just the models.
    $this->actingAs($alex)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('couple.partner.name', 'Rimma');
});

it('gives them different colours', function (): void {
    // Rule 12. Two Rosé partners make every reveal screen ambiguous, and a
    // seeder producing a couple the app would reject is worse than no seeder.
    $alex = User::firstWhere('email', 'alex@user.com');
    $rimma = User::firstWhere('email', 'rimma@user.com');

    expect($alex->colour)->not->toBeNull()
        ->and($rimma->colour)->not->toBeNull()
        ->and($alex->colour)->not->toBe($rimma->colour);
});

it('leaves one user solo, for the invite and waiting screens', function (): void {
    $robin = User::firstWhere('email', 'robin@user.com');

    expect($robin->isCoupled())->toBeFalse();

    $this->actingAs($robin)->getJson('/api/me')->assertOk()->assertJsonPath('couple', null);
});

it('can be run again without blowing up', function (): void {
    // The one that actually earns its keep. The database enforces one active
    // couple per user, so a second run that blindly created a couple would die
    // on the unique index — and `db:seed` has to stay safe to re-run, because
    // re-running it is the quickest way out of a forgotten password.
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'alex@user.com')->count())->toBe(1)
        ->and(User::firstWhere('email', 'alex@user.com')->partner()?->name)->toBe('Rimma');

    $this->postJson('/api/auth/login', [
        'email' => 'alex@user.com',
        'password' => DatabaseSeeder::PASSWORD,
    ])->assertOk();
});

it('resets the password on a re-run, which is the way out of forgetting it', function (): void {
    $alex = User::firstWhere('email', 'alex@user.com');
    $alex->forceFill(['password' => 'something-else-entirely'])->save();

    $this->seed(DatabaseSeeder::class);

    $this->postJson('/api/auth/login', [
        'email' => 'alex@user.com',
        'password' => DatabaseSeeder::PASSWORD,
    ])->assertOk();
});
