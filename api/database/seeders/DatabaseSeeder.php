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
     * **A linked couple, so the invite flow can be skipped entirely.** Partner
     * invites are awkward to exercise locally: the universal link needs
     * `link.swoon.test` hosted with its association files, and the custom
     * scheme needs a dev build on a real device. Seeding both halves means
     * signing in as either one lands straight on the tabs.
     *
     * Plus one solo user, because "signed in but not linked" is its own
     * navigation state and the invite and waiting screens need somebody to be.
     *
     * **Safe to re-run.** Everything is keyed on the email address, so
     * `php artisan db:seed` is idempotent — no `migrate:fresh` needed, and
     * nothing else in the database is touched. Running it again also resets
     * these three passwords, which is the quickest way out of having forgotten
     * one.
     *
     * No `WithoutModelEvents`: the Couple model allocates its public ULID in a
     * `creating` hook, so suppressing events here would seed couples with no
     * public id — which nothing would notice until an endpoint returned null.
     */
    public const PASSWORD = 'passwordswoon';

    public function run(): void
    {
        // Partner colours must differ (rule 12), so they are set here rather
        // than left to chance.
        $alex = $this->person('Alex', 'alex@user.com', 'rose');
        $rimma = $this->person('Rimma', 'rimma@user.com', 'lavender');

        $this->link($alex, $rimma);

        // Solo on purpose. Do not link this one.
        $this->person('Robin', 'robin@user.com', null);

        $this->command?->info('Seeded. Sign in as alex@user.com or rimma@user.com — password '.self::PASSWORD);
    }

    private function person(string $name, string $email, ?string $colour): User
    {
        $user = User::withTrashed()->firstWhere('email', $email) ?? new User;

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            // The model's `hashed` cast does the work; a plain string here is
            // correct and is never stored as one.
            'password' => self::PASSWORD,
            'email_verified_at' => now(),
            'colour' => $colour,
            'timezone' => 'Europe/London',
            'locale' => 'en-GB',
            // In case a previous run of the app soft-deleted them.
            'deleted_at' => null,
        ])->save();

        return $user;
    }

    /**
     * Link the two, unless either is already linked.
     *
     * The database enforces one active couple per user, so blindly creating a
     * couple on a second run would fail on the unique index rather than being
     * a harmless no-op.
     */
    private function link(User $alex, User $rimma): void
    {
        if ($alex->isCoupled() || $rimma->isCoupled()) {
            return;
        }

        Couple::factory()->of($alex, $rimma)->create([
            'anniversary_on' => now()->subYear()->startOfDay(),
            'first_date_on' => now()->subYears(2)->startOfDay(),
        ]);
    }
}
