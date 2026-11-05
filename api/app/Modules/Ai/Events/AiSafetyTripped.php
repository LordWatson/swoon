<?php

declare(strict_types=1);

namespace App\Modules\Ai\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * The screen found something, and one person needs the signpost.
 *
 * Its own event rather than an `AiGenerationFailed` with a reason, because a
 * listener that treats it like a failure would do exactly the wrong thing:
 * "that didn't work, try again" is not the response to somebody describing a
 * crisis.
 *
 * **It carries one user id and no couple.** The signpost goes to the person
 * whose words tripped it and to nobody else — a partner sees an ordinary
 * failure (ADR-025). There is no category and no content here, and nothing
 * that handles this event may persist the fact that it happened for a
 * particular person (ADR-027).
 *
 * It is dispatched in the worker and has no listener yet. How a signpost
 * actually reaches somebody without recording that it was sent is an open
 * question for the first feature that needs it; the module doc lists it.
 */
final readonly class AiSafetyTripped
{
    use Dispatchable;

    public function __construct(
        /** Who wrote the words. Null when it was the draft output that tripped. */
        public ?int $signpostUserId,
        public string $module,
        public string $task,
    ) {}
}
