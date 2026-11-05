<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

/**
 * A prompt file, at a pinned version, with its variables filled in.
 *
 * The version travels with the text rather than being looked up again later,
 * because every `ai_usage` and `ai_generations` row records which version
 * produced it — and a version read twice is a version that can disagree with
 * itself across a config change mid-job.
 */
final readonly class RenderedPrompt
{
    public function __construct(
        public string $name,
        public string $version,
        public string $text,
    ) {}

    /** "safety_screen/v1" — what goes on the usage row. */
    public function reference(): string
    {
        return $this->name.'/'.$this->version;
    }
}
