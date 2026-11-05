<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

/**
 * The answer from one safety screen.
 *
 * It carries no part of the text it screened, and nothing a caller could use
 * to tell a partner what was found. `$concern` is the model's own
 * classification and exists for one reason: the eval command compares it with
 * what the battery expected. **It is never persisted, never logged and never
 * returned to a client** — a record that this couple raised an abuse concern
 * on this date is precisely the kind of data that can hurt the person it is
 * about, and a product that holds it has created a risk that did not exist
 * before.
 *
 * `$attempts` is kept because the screen is a model call like any other and
 * `ai_usage` has to show what it cost, including when it failed.
 */
final readonly class SafetyVerdict
{
    /**
     * @param  list<AiResult>  $attempts
     */
    private function __construct(
        public SafetyState $state,
        public string $concern,
        public array $attempts,
    ) {}

    /**
     * Read a classification the model gave us.
     *
     * Anything but `none` is a concern, `unclear` included — that is what
     * "fails closed" means here. A model that cannot tell has told us
     * something, and it is not that everything is fine.
     *
     * @param  list<AiResult>  $attempts
     */
    public static function from(string $concern, array $attempts): self
    {
        return new self(
            state: $concern === 'none' ? SafetyState::Safe : SafetyState::Concerning,
            concern: $concern,
            attempts: $attempts,
        );
    }

    /**
     * The screen did not run, or did not come back usable.
     *
     * @param  list<AiResult>  $attempts
     */
    public static function unusable(array $attempts = []): self
    {
        return new self(SafetyState::Unusable, 'unknown', $attempts);
    }

    public function isSafe(): bool
    {
        return $this->state === SafetyState::Safe;
    }

    public function isConcerning(): bool
    {
        return $this->state === SafetyState::Concerning;
    }

    public function isUnusable(): bool
    {
        return $this->state === SafetyState::Unusable;
    }

    /** True when the generation must not go ahead, whichever reason. */
    public function stopsTheGeneration(): bool
    {
        return ! $this->isSafe();
    }
}
