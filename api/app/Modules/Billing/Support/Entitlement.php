<?php

declare(strict_types=1);

namespace App\Modules\Billing\Support;

use App\Modules\Accounts\Models\Couple;

/**
 * What a couple has paid for.
 *
 * Billing is Phase 8. Until then every couple is on `spark`, and this is the
 * **one** place that says so — it was previously hardcoded in `MeResource`,
 * and a second copy appearing in `CapGuard` is exactly how a stub becomes two
 * stubs that disagree.
 *
 * shortcut: everybody is on the free tier, so paid caps are unreachable and
 * untestable except by overriding config. Upgrade in Phase 8, when a
 * subscription record exists to read: this method becomes a lookup and
 * nothing that calls it has to change.
 */
final class Entitlement
{
    public const SPARK = 'spark';

    public static function tierFor(Couple $couple): string
    {
        return self::SPARK;
    }

    /**
     * The shape `/api/me` returns, so the app can gate features today against
     * the payload it will still be getting after Phase 8.
     *
     * @return array<string, mixed>
     */
    public static function describe(?Couple $couple): array
    {
        return [
            'tier' => $couple === null ? self::SPARK : self::tierFor($couple),
            'trial_ends_at' => null,
            'is_paid' => false,
        ];
    }
}
