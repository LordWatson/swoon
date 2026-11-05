<?php

declare(strict_types=1);

namespace App\Modules\Accounts\Http\Controllers;

use App\Models\User;
use App\Modules\Accounts\Actions\DeleteAccount;
use App\Modules\Accounts\Jobs\BuildExportForUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export and delete: the two exits.
 *
 * Both are the user's own account only — `UserPolicy` allows nothing else, and
 * neither route takes a user id for the caller to tamper with.
 */
final class PrivacyController
{
    /**
     * Ask for a copy of everything (UK GDPR Article 20).
     *
     * 202, because the work happens on the queue and the answer arrives by
     * email. Returning the file inline would mean holding a request open while
     * media is copied out of object storage.
     */
    public function export(Request $request): JsonResponse
    {
        BuildExportForUser::dispatch($request->user()->getKey());

        return response()->json([
            'message' => 'We are putting your copy together. It will be in your inbox shortly 📦',
        ], 202);
    }

    /**
     * Serve an export behind a signed, expiring link.
     *
     * The `signed` middleware proves we issued the URL and that it has not
     * expired. This still checks the caller **is** the owner: a signature says
     * the link is genuine, not that the person holding it is the right person,
     * and these links travel by email.
     */
    public function download(Request $request, User $user, string $path): StreamedResponse
    {
        abort_unless($request->user()?->is($user) === true, 403);

        $decoded = base64_decode($path, strict: true);

        // Never let a signed parameter become a path traversal. The signature
        // means we minted this value, but defence in depth is free here.
        abort_if($decoded === false || ! str_starts_with($decoded, 'exports/'), 404);
        abort_if(str_contains($decoded, '..'), 404);

        abort_unless(Storage::exists($decoded), 404);

        return Storage::download($decoded, 'swoon-export.json');
    }

    /**
     * Delete the account. Immediate from the user's point of view.
     */
    public function destroy(Request $request, DeleteAccount $action): JsonResponse
    {
        // Typing the word is the confirmation. A checkbox is too easy to tap
        // by accident for something this final (UX rule 10).
        $request->validate([
            'confirmation' => ['required', 'string', 'in:DELETE'],
        ], [
            'confirmation.in' => 'Type DELETE to confirm.',
            'confirmation.required' => 'Type DELETE to confirm.',
        ]);

        $action->handle($request->user());

        return response()->json(status: 204);
    }
}
