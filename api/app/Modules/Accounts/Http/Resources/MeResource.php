<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Resources;

use App\Models\User;
use App\Modules\Billing\Support\Entitlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything the app needs on boot, in one request.
 *
 * `couple` is null for a solo user, and that null is what the app routes on:
 * three navigation states — unauthenticated, signed in but solo, coupled —
 * and this is the single source of which one you are in.
 *
 * @mixin User
 */
final class MeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $couple = $this->resource->couple();

        return [
            'user' => new UserResource($this->resource),
            'couple' => $couple === null ? null : new CoupleResource($couple),
            'entitlement' => Entitlement::describe($couple),
        ];
    }
}
