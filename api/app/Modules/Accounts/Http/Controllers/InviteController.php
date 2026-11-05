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
use Illuminate\Validation\ValidationException;
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

    /**
     * Nudge the partner who has not joined yet.
     *
     * The cooldown is enforced **here**, not in the app: a client-side timer is
     * advisory, and the whole point of a cooldown is that it cannot become
     * pestering (UX rule 4). It shares `reminded_at` with the scheduled
     * reminder on purpose — a manual nudge and an automatic one are the same
     * email out of the same "do not pester" budget, so one suppresses the
     * other rather than both arriving.
     */
    public function remind(string $code, Request $request): JsonResponse
    {
        $invite = $this->findByCode($code);

        abort_unless($invite->inviter->is($request->user()), 404);

        if (! $invite->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['That invite is no longer live.'],
            ]);
        }

        if ($invite->email === null) {
            throw ValidationException::withMessages([
                'code' => ['There is no email address on that invite to nudge.'],
            ]);
        }

        $cooldown = (int) config('swoon.nudges.cooldown_hours');

        if ($invite->reminded_at !== null && $invite->reminded_at->diffInHours(now()) < $cooldown) {
            throw ValidationException::withMessages([
                'code' => ['You nudged them recently. Give it a day 😊'],
            ]);
        }

        SendInviteEmail::dispatch($invite, isReminder: true);

        return response()->json(['message' => 'Nudged 👀'], 202);
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
