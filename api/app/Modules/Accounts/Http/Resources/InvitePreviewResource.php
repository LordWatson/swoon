<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What an invite looks like to someone who has not signed in yet.
 *
 * **First name only.** This endpoint is unauthenticated and the code travels
 * through messaging apps, so anything in here is effectively public to whoever
 * holds the link: no surname, no email, no avatar, no couple, no user id.
 * `Str::before($name, ' ')` is the whole privacy mechanism and it is the reason
 * this is a separate resource rather than a flag on `InviteResource`.
 *
 * @mixin CoupleInvite
 */
final class InvitePreviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'inviter_first_name' => str($this->inviter->name)->before(' ')->toString(),
            'expires_at' => $this->expires_at->toIso8601String(),
            // So the app can show "this code has expired, ask for a new one"
            // rather than a blank screen.
            'is_usable' => $this->isUsable(),
        ];
    }
}
