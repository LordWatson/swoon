<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sign in with Apple or Google.
 *
 * The app sends the provider's identity token and the nonce it generated for
 * that sign-in. The nonce is optional here only because the web flow may not
 * use one — when it is present it is checked, and a mismatch or a reuse is a
 * rejection.
 */
final class SocialSignInRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identity_token' => ['required', 'string', 'max:8192'],
            'nonce' => ['sometimes', 'nullable', 'string', 'max:255'],
            // Apple returns the person's name OUTSIDE the token and only on
            // first authorisation, so the client has to forward it.
            'name' => ['sometimes', 'nullable', 'string', 'max:80'],
            // The deliberate confirmation that lets us join a social identity
            // to an existing email account (ADR-023). Never defaults to true.
            'link_confirmed' => ['sometimes', 'boolean'],
            'device_name' => ['sometimes', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['identity_token.required' => 'That sign-in did not come through. Give it another go.'];
    }
}
