<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Sign in with an email and password.
 *
 * Two things this deliberately does not do:
 *
 * 1. **It never says whether the address exists.** A wrong address and a wrong
 *    password produce the identical 422, so the endpoint cannot be used to find
 *    out who has an account — which for a couples app is a privacy leak, not
 *    just an auth nicety.
 * 2. **It does not return early when there is no user.** It hashes against a
 *    dummy instead, so the response time is the same either way: a login that
 *    answers in 5 ms for unknown addresses and 90 ms for known ones is an
 *    enumeration oracle regardless of what the message says.
 */
final class AuthenticateUser
{
    /**
     * A real bcrypt hash of a value nobody will guess, used only to burn the
     * same CPU time when there is no account to check against.
     */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfore.ZeHDKvzZOzOZMuU9VV0sJQoRBeDB1W';

    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function handle(string $email, string $password, string $deviceName = 'app'): array
    {
        $user = User::where('email', $email)->first();

        // Three failure modes, one cost: no such account, a social-only
        // account with no password set, and a wrong password all hash against
        // something and all take the same time.
        $hash = $user instanceof User
            ? ($user->password ?? self::DUMMY_HASH)
            : self::DUMMY_HASH;

        if (! Hash::check($password, $hash) || $user === null) {
            throw ValidationException::withMessages([
                // One message for every failure. Never "no such user", never
                // "wrong password" — both tell a stranger something.
                'email' => ['Those details do not match an account.'],
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }
}
