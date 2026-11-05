<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the database for local work.
     *
     * A linked couple and a solo user, which between them cover the three
     * navigation states the app has: unauthenticated, signed in but solo, and
     * coupled. Passwords are the factory's `password`.
     *
     * No WithoutModelEvents: the Couple model allocates its public ULID in a
     * `creating` hook, so suppressing events here would seed couples with no
     * public id — which nothing would notice until an endpoint returned null.
     */
    public function run(): void
    {
        $alex = User::factory()->create([
            'name' => 'Alex',
            'email' => 'alex@example.com',
            'colour' => 'rose',
        ]);

        $sam = User::factory()->create([
            'name' => 'Sam',
            'email' => 'sam@example.com',
            'colour' => 'lavender',
        ]);

        Couple::factory()->of($alex, $sam)->create([
            'anniversary_on' => now()->subYear()->startOfDay(),
            'first_date_on' => now()->subYears(2)->startOfDay(),
        ]);

        // Solo, so the invite and waiting-for-partner screens have someone to
        // sign in as.
        User::factory()->create([
            'name' => 'Robin',
            'email' => 'robin@example.com',
        ]);
    }
}
