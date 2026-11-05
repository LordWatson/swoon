<?php

declare(strict_types=1);

namespace App\Modules\Ai\Events;

use App\Modules\Ai\Models\AiGeneration;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A couple has something new to look at.
 *
 * Carries the generation, so a feature module can notify, mark something
 * ready, or supersede a draft without needing to know how the chain works.
 */
final readonly class AiGenerationCompleted
{
    use Dispatchable;

    public function __construct(public AiGeneration $generation) {}
}
