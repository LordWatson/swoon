<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Http\Resources\MeResource;
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
}
