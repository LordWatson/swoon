<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Email sign-up.
 *
 * Password policy: **length, no composition rules.** A 10-character minimum
 * with no "must contain a symbol" nonsense is current practice (NIST SP
 * 800-63B): composition rules push people towards `Passw0rd!` and are weaker
 * than length. Documented in module.md so it is a decision, not an oversight.
 *
 * shortcut: no breached-password check, add one when sign-ups justify it.
 * Laravel's `uncompromised()` rule calls api.pwnedpasswords.com on every
 * registration, which would put a third-party network call in the sign-up path
 * and in the test suite. A local blocklist is the better answer when it matters.
 */
final class RegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(10)],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        // Warm, British, and never scolding (docs/BRAND.md § 2).
        return [
            'name.required' => 'We just need something to call you.',
            'email.required' => 'We need an email address to reach you on.',
            'email.email' => 'That does not look like an email address.',
            'email.unique' => 'There is already an account with that address.',
            'password.required' => 'Pick a password to keep your space yours.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => 'email address'];
    }
}
