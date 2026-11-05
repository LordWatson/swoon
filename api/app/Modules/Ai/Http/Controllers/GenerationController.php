<?php

declare(strict_types=1);

namespace App\Modules\Ai\Http\Controllers;

use App\Modules\Ai\Actions\AiService;
use App\Modules\Ai\Http\Requests\AiFeedbackRequest;
use App\Modules\Ai\Models\AiGeneration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Rating an AI output, and asking for a better one.
 *
 * Both routes take the generation's **ULID**, not its id, so nothing can be
 * counted or walked. The policy refuses anybody who is not a live member of
 * the couple it belongs to.
 */
final class GenerationController
{
    /**
     * Thumbs up or down.
     *
     * 204 rather than the feedback row: a rating is private to whoever left
     * it, so returning it would be the beginning of a way to read a partner's.
     */
    public function feedback(AiFeedbackRequest $request, AiGeneration $generation): JsonResponse
    {
        Gate::authorize('rate', $generation);

        $user = $request->user();

        // One opinion per person, changeable. A second tap replaces the first
        // rather than reading as two people disliking something.
        $generation->feedback()
            ->where('user_id', $user?->getKey())
            ->delete();

        $generation->rateBy(
            $user,
            (string) $request->validated('rating'),
            $request->validated('note'),
        );

        return response()->json(status: 204);
    }

    /**
     * Ask the same question again.
     *
     * The payload is the one stored on the generation - already filtered, so
     * asking again cannot widen what the model sees. It counts against the
     * cap like any other generation, and the old row is superseded rather
     * than overwritten once the new one lands.
     *
     * 202, not 200: nothing has been generated yet. No AI call happens in an
     * HTTP request.
     */
    public function regenerate(Request $request, AiGeneration $generation, AiService $ai): JsonResponse
    {
        Gate::authorize('regenerate', $generation);

        $user = $request->user();
        $couple = $user?->couple();

        if ($couple === null) {
            return response()->json(status: 404);
        }

        $verdict = $ai->run(
            module: $generation->module,
            task: $generation->task,
            payload: $generation->payload,
            couple: $couple,
            user: $user,
            supersedes: $generation,
        );

        if (! $verdict->allowed) {
            // A cap is not an error. 200 with the copy the guard wrote, so
            // the app shows it as it is rather than inventing a failure.
            return response()->json([
                'queued' => false,
                'message' => $verdict->message,
                'resets_at' => $verdict->resetsAt->toIso8601String(),
            ]);
        }

        return response()->json(['queued' => true], 202);
    }
}
