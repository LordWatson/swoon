<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The other half, as you are allowed to see them.
 *
 * Profile basics only. No email address, no social ids, no date of birth, and
 * nothing they marked private. This is a separate class rather than a
 * conditional inside `UserResource` on purpose: a privacy gate written as an
 * `if` inside a shared shape is one careless edit from leaking, whereas a
 * resource that never mentions the field cannot (module.md § Permissions).
 *
 * @mixin User
 */
final class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'colour' => $this->colour,
            'avatar_path' => $this->avatar_path,
            // Included so the app can say "tomorrow, 9am their time" rather
            // than guessing. It reveals nothing a partner does not know.
            'timezone' => $this->timezone,
        ];
    }
}
