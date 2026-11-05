<?php

declare(strict_types=1);

namespace App\Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A thumbs up or down, and optionally why.
 *
 * `user_id` is deliberately not a field. It comes from the authenticated user
 * in `AiGeneration::rateBy()`, because "rate as somebody else" is the obvious
 * thing to try and a validated request is not the place to stop it.
 */
final class AiFeedbackRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'string', 'in:up,down'],
            // Short on purpose: this is a reason, not a letter. Nobody reads
            // the long ones and storing them invites writing them.
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
