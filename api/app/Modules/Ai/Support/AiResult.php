<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use Laravel\Ai\Responses\Data\TextUsage;

/**
 * What a driver got back, before anything has vouched for it.
 *
 * `structured` is the model's claim about its own shape and has not been
 * validated yet — `SchemaValidator` does that, and `SafetyGuard` runs after
 * it. Nothing in here has been shown to a couple.
 *
 * `usage` is laravel/ai's own token record rather than a copy of its fields,
 * because it already separates cached from uncached input tokens, which is
 * what cost logging needs the day prompt caching is turned on.
 */
final readonly class AiResult
{
    /**
     * @param  array<string, mixed>  $structured  The model's own claim about its shape, not yet checked against the schema.
     * @param  string  $model  The model that actually answered, which is not always the one asked for: laravel/ai can fail over.
     */
    public function __construct(
        public array $structured,
        public string $text,
        public string $model,
        public TextUsage $usage,
    ) {}
}
