<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Http\Requests\UpdateProfileRequest;
use App\Modules\Accounts\Http\Resources\MeResource;
use App\Modules\Accounts\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * The one call the app makes on boot.
 *
 * Returns the user, their couple (or null), and their entitlement — enough for
 * the app to decide which of its three navigation states it is in without a
 * second round trip.
 */
final class MeController
{
    public function show(Request $request): MeResource
    {
        return new MeResource($request->user());
    }

    /**
     * Update profile basics.
     *
     * Onboarding asks one question per screen and PATCHes each answer as it is
     * given, so a half-finished flow still leaves the answers that were
     * reached — which is also what makes "back never loses an answer" true on
     * the server as well as in the client's draft.
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $user->update($request->validated());

        return new UserResource($user->fresh());
    }
}
