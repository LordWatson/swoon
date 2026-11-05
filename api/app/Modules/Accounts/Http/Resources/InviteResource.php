<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Modules\Accounts\Models\CoupleInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An invite, shown to the person who created it.
 *
 * Carries the code and both links, because the app offers "share" and "read it
 * out" side by side — a code that can only be tapped is useless across a
 * kitchen.
 *
 * @mixin CoupleInvite
 */
final class InviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'expires_at' => $this->expires_at->toIso8601String(),
            'email' => $this->email,
            'universal_link' => sprintf(
                'https://%s/invite/%s',
                config('swoon.deep_link.host'),
                $this->code,
            ),
            'deep_link' => sprintf(
                '%s://invite/%s',
                config('swoon.deep_link.scheme'),
                $this->code,
            ),
        ];
    }
}
