<?php

declare(strict_types=1);

/*
 * The `users` table carries the profile fields every later phase reads, and
 * Sanctum token issuance is the gate to every authenticated route — so both
 * are asserted here, before anything is built on top of them.
 */

use App\Models\User;

usingDatabase();

it('issues a working Sanctum token', function (): void {
    $user = User::factory()->create();

    $token = $user->createToken('phone');

    expect($token->plainTextToken)->toContain('|')
        ->and($user->tokens()->count())->toBe(1);
});

it('defaults a new user to the UK', function (): void {
    $user = User::factory()->create();

    expect($user->fresh()->timezone)->toBe('Europe/London')
        ->and($user->fresh()->locale)->toBe('en-GB');
});

it('round-trips the profile fields later phases rely on', function (): void {
    $user = User::factory()->create([
        'colour' => 'rose',
        'dietary_needs' => ['vegetarian', 'no shellfish'],
        'accessibility_needs' => ['step-free'],
        'date_of_birth' => '1992-04-17',
    ]);

    $fresh = $user->fresh();

    expect($fresh->dietary_needs)->toBe(['vegetarian', 'no shellfish'])
        ->and($fresh->accessibility_needs)->toBe(['step-free'])
        ->and($fresh->date_of_birth->toDateString())->toBe('1992-04-17')
        ->and($fresh->colour)->toBe('rose');
});
