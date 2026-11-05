<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user as the owner of that account sees it.
 *
 * Never use this for a partner: it carries the email address. The partner's
 * view is `PartnerResource`, which is a deliberately smaller shape — a reveal
 * gate has to be absent from the JSON, not merely unrendered.
 *
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'colour' => $this->colour,
            'avatar_path' => $this->avatar_path,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'dietary_needs' => $this->dietary_needs ?? [],
            'accessibility_needs' => $this->accessibility_needs ?? [],
        ];
    }
}
