<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Email sign-in.
 *
 * Note what is NOT here: any rule that would tell the caller whether the
 * address exists. `exists:users,email` would turn this endpoint into an account
 * enumerator, so the only checks are on the shape of the input, and a wrong
 * address and a wrong password fail identically (see `AuthenticateUser`).
 */
final class LoginRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
            // Shown in the user's device list, so they can revoke one device
            // without signing out everywhere.
            'device_name' => ['sometimes', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'We need your email address.',
            'password.required' => 'We need your password.',
        ];
    }
}
