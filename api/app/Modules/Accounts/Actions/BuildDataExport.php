<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Assemble everything a user is entitled to a copy of (UK GDPR Article 20).
 *
 * ## The shape
 *
 * Settled here and documented in `Accounts/module.md`, which listed it as an
 * open gap. `version` is the contract: a consumer reading an export can tell
 * what to expect, and a later change bumps it rather than silently moving
 * fields.
 *
 * ```
 * {
 *   "export":  { "version", "generated_at", "product" },
 *   "you":     { the full profile, as the owner sees it },
 *   "couple":  { linked_at, settings, partner: { name, colour } } | null,
 *   "invites": [ { code, email, expires_at, accepted_at, revoked_at } ],
 *   "authored": { },   // per-module, filled as modules are built
 *   "shared":   { }    // couple content this user could already see
 * }
 * ```
 *
 * ## What is NOT in it
 *
 * **Anything the partner marked private.** Not the gift vault, not their
 * individual check-in answers, not locked love notes, not private moments or
 * private love-map entries. An export is an Article 20 copy of *your* data,
 * and "I can download it" must not become a way around a reveal gate.
 *
 * The partner appears as a name and a colour and nothing else: not their
 * email, not their social ids, not their date of birth. That is the same shape
 * `PartnerResource` serves, for the same reason.
 *
 * ## EVERY MODULE MUST ADD ITSELF HERE
 *
 * Like the purge job, deliberately an explicit list rather than a registry. A
 * module that forgets to add itself produces an export that quietly omits the
 * user's data, which is the same failure as leaking it — in the other
 * direction, and just as much a compliance problem.
 */
final class BuildDataExport
{
    /** Bump when the shape changes. */
    private const VERSION = 1;

    /** Long enough to find the email, short enough not to linger. */
    public const LINK_TTL_HOURS = 48;

    /**
     * Writes the export and returns its storage path.
     *
     * The filename carries a ULID, not the user id: the download is behind a
     * signed URL, but a guessable path is one misconfiguration away from being
     * a listing.
     */
    public function handle(User $user): string
    {
        $path = 'exports/'.Str::ulid().'.json';

        Storage::disk($this->disk())->put(
            $path,
            json_encode($this->assemble($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    public function assemble(User $user): array
    {
        $couple = $user->couple();
        $partner = $couple?->partnerFor($user);

        return [
            'export' => [
                'version' => self::VERSION,
                'generated_at' => now()->toIso8601String(),
                'product' => (string) config('swoon.name'),
            ],
            'you' => [
                'name' => $user->name,
                'email' => $user->email,
                'colour' => $user->colour,
                'timezone' => $user->timezone,
                'locale' => $user->locale,
                'date_of_birth' => $user->date_of_birth?->toDateString(),
                'dietary_needs' => $user->dietary_needs ?? [],
                'accessibility_needs' => $user->accessibility_needs ?? [],
                'joined_at' => $user->created_at?->toIso8601String(),
            ],
            'couple' => $couple === null ? null : [
                'linked_at' => $couple->linked_at->toIso8601String(),
                'lock_in_mode' => $couple->lock_in_mode,
                'anniversary_on' => $couple->anniversary_on?->toDateString(),
                'first_date_on' => $couple->first_date_on?->toDateString(),
                // Name and colour only. An export of your data is not a copy
                // of theirs.
                'partner' => $partner === null ? null : [
                    'name' => $partner->name,
                    'colour' => $partner->colour,
                ],
            ],
            'invites' => $user->invites()->get()->map(fn ($invite): array => [
                'code' => $invite->code,
                'email' => $invite->email,
                'expires_at' => $invite->expires_at->toIso8601String(),
                'accepted_at' => $invite->accepted_at?->toIso8601String(),
                'revoked_at' => $invite->revoked_at?->toIso8601String(),
            ])->all(),

            // TODO P4: moments authored, with their media file names.
            // TODO P6: check-in answers, love notes sent, spark answers.
            // TODO P9: bucket list, love map, wishlist. NOT the gift vault —
            //          that is private to its author, including from an export
            //          requested by the other partner.
            'authored' => [],

            // TODO P3/P5: date plans, planner entries, milestones — couple
            //             content this user could already see in the app.
            'shared' => [],
        ];
    }

    public function disk(): string
    {
        // Exports are private by definition: never the public disk.
        return (string) config('filesystems.default');
    }
}
