<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use App\Modules\Ai\Models\AiUsage;

/**
 * The one thing that writes `ai_usage`.
 *
 * One row per model call, including the calls that failed — we were billed for
 * a reply in the wrong shape exactly as much as for a good one, and a failure
 * that quietly loses its token counts is a failure that quietly loses money.
 * Outcomes that never reached the model (`capped`) get a row too, with no
 * model and no tokens, so "nothing happened" and "it was refused" are
 * different facts in the table rather than both being absence.
 *
 * Being the only writer is what lets ADR-027 be enforced in one place: a
 * `safety_tripped` row carries no couple and no user, whatever the caller
 * passes.
 */
final readonly class UsageLogger
{
    public function record(
        AiOutcome $outcome,
        string $module,
        string $task,
        ?string $promptVersion = null,
        ?AiResult $result = null,
        ?int $coupleId = null,
        ?int $userId = null,
        int $durationMs = 0,
    ): AiUsage {
        [$promptName, $version] = $this->splitVersion($promptVersion);

        // ADR-027, in the only place it can be bypassed. Nothing in this
        // database should say that this couple tripped the safety guard on
        // this date, so the attribution is dropped here rather than left to
        // every caller to remember.
        $names = $outcome->mayNameTheCouple();

        return AiUsage::create([
            'couple_id' => $names ? $coupleId : null,
            'user_id' => $names ? $userId : null,
            'module' => $module,
            'task' => $task,
            'prompt_name' => $promptName,
            'prompt_version' => $version,
            'model' => $result?->model,
            'input_tokens' => $result?->usage->inputTokens ?? 0,
            'output_tokens' => $result?->usage->outputTokens ?? 0,
            'cost_millipence' => $result === null ? 0 : Cost::millipence($result->model, $result->usage),
            'outcome' => $outcome,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * "date_plan/v1" into its two halves.
     *
     * Split rather than stored whole, so "which prompt costs the most" and
     * "did v2 get cheaper" are both one query instead of a LIKE. A prompt name
     * cannot contain a slash — `PromptRegistry` only accepts `[a-z0-9_]` — so
     * this is unambiguous.
     *
     * @return array{string|null, string|null}
     */
    private function splitVersion(?string $reference): array
    {
        if ($reference === null) {
            return [null, null];
        }

        $parts = explode('/', $reference, 2);

        return [$parts[0], $parts[1] ?? null];
    }
}
