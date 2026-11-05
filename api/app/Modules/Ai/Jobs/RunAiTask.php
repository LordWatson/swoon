<?php

declare(strict_types=1);

namespace App\Modules\Ai\Jobs;

use App\Modules\Accounts\Models\Couple;
use App\Modules\Ai\Actions\GenerateValidated;
use App\Modules\Ai\Actions\SafetyGuard;
use App\Modules\Ai\Events\AiGenerationCompleted;
use App\Modules\Ai\Events\AiGenerationFailed;
use App\Modules\Ai\Events\AiSafetyTripped;
use App\Modules\Ai\Models\AiGeneration;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\AiResult;
use App\Modules\Ai\Support\PromptRegistry;
use App\Modules\Ai\Support\SafetyVerdict;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * The whole chain, in a worker.
 *
 * Every AI call in the product happens here and nowhere else, which is the
 * point: a slow model can never become a slow API, and there is one place to
 * read if you want to know what happens between a couple asking and a couple
 * being shown something.
 *
 *     screen each input   -> SafetyGuard, before the expensive model
 *     render the prompt   -> PromptRegistry, pinned version
 *     generate + validate -> GenerateValidated, one retry on a bad shape
 *     screen the draft    -> SafetyGuard again
 *     persist             -> ai_generations, superseding if this is a redo
 *     log                 -> ai_usage, one row per call, always
 *
 * The cap and the privacy filter happen **before** dispatch, in `AiService`,
 * so a refusal costs nothing and un-allow-listed data never reaches the
 * `jobs` table.
 *
 * **Every exit logs.** There is no `return` in here without a `ai_usage` row
 * behind it, because an unlogged call is money we cannot account for.
 *
 * **Retries.** `$tries = 2`, so a transport failure escaping the expensive
 * generation gets one more go. A safety trip is a clean return and so is never
 * retried; nor is a screen that *failed*, because SafetyGuard already decided
 * the generation is abandoned and paying twice would not change that.
 */
final class RunAiTask implements ShouldQueue
{
    use Queueable;

    /** One retry, for a transport failure on the expensive call. */
    public int $tries = 2;

    /**
     * @param  array<string, mixed>  $payload  Already filtered by the allow-list.
     * @param  array<int, string>  $screen  Free text, keyed by the id of whoever wrote it.
     */
    public function __construct(
        public string $module,
        public string $task,
        public array $payload,
        public int $coupleId,
        public ?int $userId = null,
        public array $screen = [],
        public ?int $supersedesId = null,
    ) {}

    public function handle(
        PromptRegistry $prompts,
        GenerateValidated $generate,
        SafetyGuard $safety,
        UsageLogger $usage,
    ): void {
        $couple = Couple::find($this->coupleId);

        if ($couple === null) {
            // Deleted or unlinked while this sat in the queue. Nothing to show
            // anybody, and nothing was spent.
            return;
        }

        foreach ($this->screen as $writerId => $text) {
            $verdict = $safety->screen((string) $text);

            $this->logScreen($usage, $verdict);

            if ($verdict->isConcerning()) {
                $this->trip($usage, (int) $writerId);

                return;
            }

            if ($verdict->isUnusable()) {
                $this->fail($usage, AiOutcome::Error);

                return;
            }
        }

        try {
            $prompt = $prompts->resolve($this->task, $this->payload);
        } catch (Throwable) {
            // An unpinned prompt, a missing file, a slot with no value. Not
            // retryable: it will be just as missing next time.
            $this->fail($usage, AiOutcome::Error);

            return;
        }

        // A transport failure here escapes on purpose, so the queue retries
        // the expensive call once.
        $generation = $generate->handle(new AiRequest(
            task: $this->task,
            prompt: $prompt->text,
            promptVersion: $prompt->reference(),
        ));

        $attempts = $generation->attempts;
        // The last attempt's row carries the terminal outcome; the ones before
        // it were each a reply in the wrong shape, and each was billed.
        $final = array_pop($attempts);

        foreach ($attempts as $earlier) {
            $this->log($usage, AiOutcome::InvalidOutput, $prompt->reference(), $earlier);
        }

        if (! $generation->isValid()) {
            $this->log($usage, AiOutcome::InvalidOutput, $prompt->reference(), $final);
            $this->dispatchFailure(AiOutcome::InvalidOutput);

            return;
        }

        $draft = $safety->screen((string) json_encode($generation->structured));

        $this->logScreen($usage, $draft);

        if ($draft->isConcerning()) {
            // The draft itself is the problem, so there is no writer to name:
            // the signpost goes to whoever asked.
            $this->log($usage, AiOutcome::SafetyTripped, $prompt->reference(), $final);
            AiSafetyTripped::dispatch($this->userId, $this->module, $this->task);

            return;
        }

        if ($draft->isUnusable()) {
            $this->log($usage, AiOutcome::Error, $prompt->reference(), $final);
            $this->dispatchFailure(AiOutcome::Error);

            return;
        }

        $row = AiGeneration::create([
            'couple_id' => $this->coupleId,
            'module' => $this->module,
            'task' => $this->task,
            'prompt_name' => $prompt->name,
            'prompt_version' => $prompt->version,
            'payload' => $this->payload,
            'output' => $generation->structured,
        ]);

        if ($this->supersedesId !== null) {
            // A write to the old row, never over it. What they were shown in
            // March survives them asking for something better in April.
            AiGeneration::find($this->supersedesId)?->supersede($row);
        }

        $this->log($usage, AiOutcome::Ok, $prompt->reference(), $final);

        AiGenerationCompleted::dispatch($row);
    }

    /**
     * A safety trip: nothing is persisted, nothing is logged about what was
     * found, and one person gets the signpost.
     */
    private function trip(UsageLogger $usage, int $writerId): void
    {
        // No prompt version and no result: the expensive model was never
        // asked. The row says a generation was abandoned and names nobody
        // (ADR-027); the screen's own cost is on its own rows.
        $usage->record(
            outcome: AiOutcome::SafetyTripped,
            module: $this->module,
            task: $this->task,
            coupleId: $this->coupleId,
            userId: $this->userId,
        );

        AiSafetyTripped::dispatch($writerId, $this->module, $this->task);
    }

    private function fail(UsageLogger $usage, AiOutcome $outcome): void
    {
        $this->log($usage, $outcome, null, null);
        $this->dispatchFailure($outcome);
    }

    private function dispatchFailure(AiOutcome $outcome): void
    {
        AiGenerationFailed::dispatch($this->coupleId, $this->userId, $this->module, $this->task, $outcome);
    }

    private function log(UsageLogger $usage, AiOutcome $outcome, ?string $promptVersion, ?AiResult $result): void
    {
        $usage->record(
            outcome: $outcome,
            module: $this->module,
            task: $this->task,
            promptVersion: $promptVersion,
            result: $result,
            coupleId: $this->coupleId,
            userId: $this->userId,
        );
    }

    /**
     * A row for each call the screen made, whatever it concluded.
     *
     * The outcome describes the *screen*, not the generation: a screen that
     * ran and answered is `ok` even when what it found stops everything. What
     * it found is on the generation's own row, which names no couple.
     */
    private function logScreen(UsageLogger $usage, SafetyVerdict $verdict): void
    {
        $outcome = $verdict->isUnusable() ? AiOutcome::Error : AiOutcome::Ok;

        if ($verdict->attempts === []) {
            // The screen never reached the model - empty text, or it broke
            // before it could. Still a row, so the chain has no silent step.
            $usage->record(
                outcome: $outcome,
                module: $this->module,
                task: 'safety_screen',
                coupleId: $this->coupleId,
                userId: $this->userId,
            );

            return;
        }

        foreach ($verdict->attempts as $attempt) {
            $usage->record(
                outcome: $outcome,
                module: $this->module,
                task: 'safety_screen',
                promptVersion: 'safety_screen/'.(string) config('ai.prompts.active.safety_screen'),
                result: $attempt,
                coupleId: $this->coupleId,
                userId: $this->userId,
            );
        }
    }
}
