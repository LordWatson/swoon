<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Profile basics, as set during onboarding and in settings.
 *
 * Note what is **not** here: email, password, and the social subject ids. An
 * email change needs re-verification and a password change needs the old one,
 * so both are their own flows rather than a field on this request — and
 * `apple_sub`/`google_sub` must never be settable by a client at all.
 *
 * Every field is `sometimes`, because onboarding submits one answer per screen
 * (UX rule 3) and a partial update must not wipe what came before.
 */
final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:80'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', 'max:10'],
            // The four partner colours. A free string here would let a client
            // set a colour the theme has no token for.
            'colour' => ['sometimes', 'nullable', Rule::in(['rose', 'lavender', 'apricot', 'sage'])],
            'date_of_birth' => ['sometimes', 'nullable', 'date', 'before:today'],
            // Chips, so a list of short strings. Capped because this feeds an
            // AI prompt later and unbounded user text in a prompt is a problem
            // for a different reason.
            'dietary_needs' => ['sometimes', 'nullable', 'array', 'max:20'],
            'dietary_needs.*' => ['string', 'max:40'],
            'accessibility_needs' => ['sometimes', 'nullable', 'array', 'max:20'],
            'accessibility_needs.*' => ['string', 'max:40'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before' => 'That birthday is in the future 🎂',
            'colour.in' => 'Pick one of the four colours.',
        ];
    }
}
