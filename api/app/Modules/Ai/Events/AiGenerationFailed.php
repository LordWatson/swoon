<?php

declare(strict_types=1);

namespace App\Modules\Ai\Events;

use App\Modules\Ai\Support\AiOutcome;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Nothing came back, and the couple is waiting.
 *
 * `outcome` is why — `capped`, `invalid_output` or `error` — so a feature can
 * decide between "that didn't work, try again" and showing the cap refusal it
 * already has. It is never `safety_tripped`: that has its own event, because
 * it is not a failure and must not be handled like one.
 */
final readonly class AiGenerationFailed
{
    use Dispatchable;

    public function __construct(
        public int $coupleId,
        public ?int $userId,
        public string $module,
        public string $task,
        public AiOutcome $outcome,
    ) {}
}
