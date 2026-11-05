<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Actions\UnlinkCouple;
use App\Modules\Accounts\Http\Resources\CoupleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The couple: view it, change its settings, end it.
 *
 * There is no couple id in any of these routes. A user has at most one, the
 * database guarantees it, and taking an id would only create a way to ask
 * about somebody else's.
 */
final class CoupleController
{
    public function show(Request $request): CoupleResource
    {
        $couple = $request->user()->couple();

        abort_if($couple === null, 404);

        return new CoupleResource($couple);
    }

    public function update(Request $request): CoupleResource
    {
        $couple = $request->user()->couple();

        abort_if($couple === null, 404);

        // Either partner may change these. There is no owner (rule 3).
        $validated = $request->validate([
            'lock_in_mode' => ['sometimes', Rule::in(['both_heart', 'either_locks'])],
            'anniversary_on' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
            'first_date_on' => ['sometimes', 'nullable', 'date', 'before_or_equal:today'],
        ]);

        $couple->update($validated);

        return new CoupleResource($couple->fresh());
    }

    /**
     * Unlink. Immediately, no consent from the other partner (rule 3).
     */
    public function destroy(Request $request, UnlinkCouple $action): JsonResponse
    {
        $couple = $request->user()->couple();

        abort_if($couple === null, 404);

        // The policy still runs: it is what refuses an already-closed couple
        // and anyone who is not a live member.
        abort_unless($request->user()->can('unlink', $couple), 403);

        $action->handle($couple, $request->user());

        return response()->json(status: 204);
    }
}
