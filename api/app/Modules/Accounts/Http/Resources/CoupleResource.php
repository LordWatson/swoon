<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Modules\Accounts\Models\Couple;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The couple, for one of its partners.
 *
 * The id in the payload is the ULID, never the auto-increment key: a
 * sequential id in a URL tells anyone who looks roughly how many couples the
 * product has.
 *
 * @mixin Couple
 */
final class CoupleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        return [
            'id' => $this->ulid,
            'linked_at' => $this->linked_at->toIso8601String(),
            'lock_in_mode' => $this->lock_in_mode,
            'anniversary_on' => $this->anniversary_on?->toDateString(),
            'first_date_on' => $this->first_date_on?->toDateString(),
            'partner' => $viewer === null
                ? null
                : new PartnerResource($this->partnerFor($viewer)),
        ];
    }
}
