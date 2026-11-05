<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use RuntimeException;

/**
 * The outcome of asking the model once, or twice, for a valid shape.
 *
 * It holds **every** attempt, because every attempt was billed and
 * `ai_usage` has to show what we paid even when the answer was useless. A
 * failure that quietly loses its token counts is a failure that quietly loses
 * money.
 *
 * `structured` is null unless the reply passed the schema, so a caller cannot
 * reach malformed output by mistake — the type says it might not be there.
 */
final readonly class Generation
{
    /**
     * @param  list<AiResult>  $attempts  Every call made, in order. All of them cost money.
     * @param  array<string, mixed>|null  $structured  The validated output, or null when nothing valid came back.
     * @param  list<string>  $problems  Why the last attempt was rejected. Empty on success.
     */
    public function __construct(
        public array $attempts,
        public ?array $structured,
        public array $problems,
    ) {}

    public function isValid(): bool
    {
        return $this->structured !== null;
    }

    /** The attempt whose output we kept, or the last one we gave up on. */
    public function last(): AiResult
    {
        // Not end(): that takes the array by reference, which a readonly
        // property will not allow.
        $last = $this->attempts[count($this->attempts) - 1] ?? null;

        if (! $last instanceof AiResult) {
            throw new RuntimeException('A generation with no attempts should not exist.');
        }

        return $last;
    }
}
