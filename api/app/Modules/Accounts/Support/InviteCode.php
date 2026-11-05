<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Support;

use Illuminate\Support\Str;

/**
 * The partner invite code.
 *
 * Partners read these to each other aloud, over the phone or across a kitchen,
 * so the alphabet deliberately excludes every character that gets misheard or
 * misread: 0 and O, 1 and I and L. What is left is unambiguous spoken and
 * written, in either case (rule 7).
 *
 * Codes are generated with `Str::random`, which uses a cryptographically secure
 * source — a guessable code would let a stranger join someone's couple.
 */
final class InviteCode
{
    /**
     * 0/O, 1/I/L excluded. Uppercase only, so a code is never ambiguous about
     * its own case when spoken.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $length = (int) config('swoon.invites.code_length');
        $alphabet = str_split(self::ALPHABET);
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            // random_int, via Str, rather than array_rand: this is a secret.
            $code .= $alphabet[random_int(0, count($alphabet) - 1)];
        }

        return $code;
    }

    /** Normalises what a person typed: trims, uppercases, drops spaces. */
    public static function normalise(string $input): string
    {
        return Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $input) ?? '');
    }

    /** True if the string could be one of our codes at all. */
    public static function looksValid(string $code): bool
    {
        $length = (int) config('swoon.invites.code_length');

        return (bool) preg_match('/^['.self::ALPHABET.']{'.$length.'}$/', $code);
    }
}
