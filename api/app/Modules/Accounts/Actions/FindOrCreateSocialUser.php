<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Actions;

use App\Models\User;
use App\Modules\Accounts\Support\SocialIdentity;
use Illuminate\Validation\ValidationException;

/**
 * Resolve a verified social identity to an account.
 *
 * Three cases, and the third is the one with the sharp edge:
 *
 * 1. **We know this subject id** → that is the user. Sign them in.
 * 2. **We do not, and the email is free** → create an account.
 * 3. **We do not, but the email already belongs to an account** → stop and
 *    ask. Linking happens only on an explicit confirmation from the person
 *    (ADR-023), because an email match is not proof of being the same human:
 *    anyone who can get a provider to issue a token for an address could
 *    otherwise walk into an existing account.
 *
 * Accounts are keyed on the provider's subject id, **never** on the email. An
 * Apple user can change their relay address, and a Google account's address can
 * change hands.
 */
final class FindOrCreateSocialUser
{
    /**
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function handle(
        SocialIdentity $identity,
        bool $linkConfirmed = false,
        string $deviceName = 'app',
    ): array {
        $column = $identity->subjectColumn();

        $user = User::where($column, $identity->subject)->first();

        if ($user === null) {
            $user = $this->claimOrCreate($identity, $column, $linkConfirmed);
        }

        $this->backfill($user, $identity);

        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }

    /**
     * @throws ValidationException
     */
    private function claimOrCreate(SocialIdentity $identity, string $column, bool $linkConfirmed): User
    {
        // A relay address is specific to this app and is not an identity, so it
        // never matches an existing account on purpose.
        $existing = $identity->email !== null && ! $identity->isPrivateRelayEmail
            ? User::where('email', $identity->email)->first()
            : null;

        if ($existing !== null && ! $linkConfirmed) {
            throw ValidationException::withMessages([
                'link_confirmed' => [
                    'There is already an account with that email address. '
                    .'Confirm that it is yours and we will join them up.',
                ],
            ]);
        }

        if ($existing !== null) {
            $existing->forceFill([$column => $identity->subject])->save();

            return $existing;
        }

        $user = new User([
            // Apple may offer no name at all, and a nameless account is worse
            // than a placeholder the person can change in settings.
            'name' => $identity->name ?? 'Someone lovely',
            'email' => $this->claimableEmail($identity),
            'password' => null,
            'timezone' => config('app.timezone'),
        ]);

        // The subject id is set here, not mass-assigned: `apple_sub` and
        // `google_sub` are deliberately absent from the model's fillable list,
        // so a stray `User::create($request->all())` anywhere in the app can
        // never hand someone another person's social identity.
        $user->forceFill([$column => $identity->subject])->save();

        return $user;
    }

    /**
     * The email to store on a brand-new account, or null.
     *
     * `users.email` is unique, so an address already in use cannot be stored
     * again. That only happens for an address we refused to match on — an
     * Apple relay address, which is specific to one person *and* one app and
     * so should never collide in practice. Storing null rather than failing
     * keeps the account valid: it is identified by its subject id, and
     * `email` is nullable exactly for accounts like this one.
     */
    private function claimableEmail(SocialIdentity $identity): ?string
    {
        if ($identity->email === null) {
            return null;
        }

        return User::where('email', $identity->email)->exists() ? null : $identity->email;
    }

    /**
     * Fill in what was missing, but never overwrite what the user has set.
     *
     * Apple hands over the real name only on first authorisation, so a user
     * who signed up before the app asked for it would otherwise be stuck with
     * the placeholder for good. A name the user has since chosen themselves is
     * left alone.
     */
    private function backfill(User $user, SocialIdentity $identity): void
    {
        $changes = [];

        if ($identity->name !== null && $user->name === 'Someone lovely') {
            $changes['name'] = $identity->name;
        }

        if ($user->email === null && $identity->email !== null
            && ! User::where('email', $identity->email)->whereKeyNot($user->getKey())->exists()) {
            $changes['email'] = $identity->email;
        }

        if ($changes !== []) {
            $user->forceFill($changes)->save();
        }
    }
}
