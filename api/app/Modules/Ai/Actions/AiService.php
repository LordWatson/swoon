<?php

declare(strict_types=1);

namespace App\Modules\Ai\Actions;

use App\Models\User;
use App\Modules\Accounts\Models\Couple;
use App\Modules\Ai\Jobs\RunAiTask;
use App\Modules\Ai\Models\AiGeneration;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\CapVerdict;
use App\Modules\Ai\Support\PrivacyFilter;
use App\Modules\Ai\Support\UsageLogger;

/**
 * The only way into the model.
 *
 * A feature module calls `run()` and gets back a `CapVerdict`: refused, with
 * copy to show, or allowed, with a job queued. It never sees a driver, a
 * prompt file or a token count.
 *
 * Two things happen here rather than in the job, both on purpose.
 *
 * **The cap is checked before dispatch**, so a refusal costs nothing at all -
 * not a model call, not a queue slot, not a worker's attention.
 *
 * **The payload is filtered before dispatch**, so un-allow-listed data never
 * reaches the `jobs` table. The plan put the filter inside the job; putting it
 * one step earlier keeps private fields out of a row that sits on disk until a
 * worker picks it up, and the guarantee the filter exists for is unchanged -
 * nothing reaches a prompt that was not allow-listed.
 *
 * shortcut: two simultaneous requests can both pass the cap check and both
 * dispatch, because the check is a read. The per-couple cost guard is the
 * backstop, and the window is one queue round-trip wide. Upgrade if it ever
 * matters: a conditional insert on a reservation row, the way invite accepts
 * are guarded.
 */
final readonly class AiService
{
    public function __construct(
        private CapGuard $caps,
        private PrivacyFilter $privacy,
        private UsageLogger $usage,
    ) {}

    /**
     * Queue one generation, unless a cap says no.
     *
     * @param  string  $module  The module asking, e.g. "DatePlanner".
     * @param  string  $task  A key in `config('ai.tasks')`.
     * @param  array<string, mixed>  $payload  Unfiltered. The allow-list decides what survives.
     * @param  array<int, string>  $screen  Free text to screen, keyed by the id of the person who wrote it, so a signpost can reach them and nobody else (ADR-025).
     * @param  AiGeneration|null  $supersedes  Set when this is a regenerate.
     */
    public function run(
        string $module,
        string $task,
        array $payload,
        Couple $couple,
        ?User $user = null,
        array $screen = [],
        ?AiGeneration $supersedes = null,
    ): CapVerdict {
        $verdict = $this->caps->check($couple, $task);

        if (! $verdict->allowed) {
            // A refusal is still a thing that happened, and the table has to
            // say so: "nothing happened" and "we said no" are different facts.
            $this->usage->record(
                outcome: AiOutcome::Capped,
                module: $module,
                task: $task,
                coupleId: $couple->getKey(),
                userId: $user?->getKey(),
            );

            return $verdict;
        }

        RunAiTask::dispatch(
            module: $module,
            task: $task,
            payload: $this->privacy->apply($task, $payload),
            coupleId: (int) $couple->getKey(),
            userId: $user === null ? null : (int) $user->getKey(),
            screen: $screen,
            supersedesId: $supersedes === null ? null : (int) $supersedes->getKey(),
        );

        return $verdict;
    }
}
