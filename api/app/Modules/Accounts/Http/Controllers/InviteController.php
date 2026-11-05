<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Modules\Accounts\Actions\AcceptInvite;
use App\Modules\Accounts\Actions\CreateInvite;
use App\Modules\Accounts\Actions\RevokeInvite;
use App\Modules\Accounts\Http\Resources\CoupleResource;
use App\Modules\Accounts\Http\Resources\InvitePreviewResource;
use App\Modules\Accounts\Http\Resources\InviteResource;
use App\Modules\Accounts\Jobs\SendInviteEmail;
use App\Modules\Accounts\Models\CoupleInvite;
use App\Modules\Accounts\Support\InviteCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Create, preview, accept and revoke a partner invite.
 */
final class InviteController
{
    public function store(Request $request, CreateInvite $action): InviteResource
    {
        $validated = $request->validate([
            'email' => ['sometimes', 'nullable', 'string', 'email:rfc', 'max:255'],
        ]);

        $invite = $action->handle($request->user(), $validated['email'] ?? null);

        if ($invite->email !== null) {
            SendInviteEmail::dispatch($invite);
        }

        return new InviteResource($invite);
    }

    /**
     * Unauthenticated preview: "Alex would like to share a space with you".
     *
     * Returns first name only (see `InvitePreviewResource`). A code that does
     * not exist and a code that is malformed both 404 — the endpoint must not
     * become a way to test codes in bulk and learn which shapes are real.
     */
    public function show(string $code): InvitePreviewResource
    {
        $invite = $this->findByCode($code);

        return new InvitePreviewResource($invite);
    }

    public function accept(string $code, Request $request, AcceptInvite $action): CoupleResource
    {
        $invite = $this->findByCode($code);

        $couple = $action->handle($invite, $request->user());

        return new CoupleResource($couple);
    }

    public function destroy(CoupleInvite $invite, Request $request, RevokeInvite $action): JsonResponse
    {
        // Authorisation before anything else: an invite id is guessable, so a
        // stranger must get nothing, not a validation error that confirms it
        // exists.
        abort_unless($invite->inviter->is($request->user()), 404);

        $action->handle($invite, $request->user());

        return response()->json(status: 204);
    }

    private function findByCode(string $code): CoupleInvite
    {
        $normalised = InviteCode::normalise($code);

        if (! InviteCode::looksValid($normalised)) {
            throw new NotFoundHttpException;
        }

        $invite = CoupleInvite::where('code', $normalised)->first();

        if ($invite === null) {
            throw new NotFoundHttpException;
        }

        return $invite;
    }
}
