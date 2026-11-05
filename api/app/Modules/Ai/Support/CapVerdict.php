<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use Illuminate\Support\Carbon;

/**
 * Whether a couple may ask for this, and what to say if not.
 *
 * The refusal travels with the verdict rather than being written wherever a
 * cap is checked, so there is one piece of copy per reason instead of one per
 * caller. UX rule 8: no guilt, and say when it comes back.
 */
final readonly class CapVerdict
{
    private function __construct(
        public bool $allowed,
        public CapReason $reason,
        /** Successful generations this month. */
        public int $used,
        /** 0 means the feature is not on this tier at all. */
        public int $limit,
        /** Local midnight on the first of next month, in the couple's timezone. */
        public Carbon $resetsAt,
        /** Null when allowed. Shown to the couple as written. */
        public ?string $message,
    ) {}

    public static function allowed(int $used, int $limit, Carbon $resetsAt): self
    {
        return new self(true, CapReason::Allowed, $used, $limit, $resetsAt, null);
    }

    public static function refused(CapReason $reason, int $used, int $limit, Carbon $resetsAt, string $message): self
    {
        return new self(false, $reason, $used, $limit, $resetsAt, $message);
    }

    public function remaining(): int
    {
        return max(0, $this->limit - $this->used);
    }
}
