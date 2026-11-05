<?php

declare(strict_types=1);

namespace App\Modules\Ai\Contracts;

use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\AiResult;

/**
 * The transport step, and only the transport step.
 *
 * A driver sends one already-rendered prompt and hands back what came out. It
 * does not validate the shape, screen for safety, filter the payload or log
 * the cost — those are separate, separately tested steps, chained by
 * `RunAiTask`. Keeping them out of here is what lets the whole suite run with
 * `AI_DRIVER=fake` and still exercise every rule.
 *
 * Transport failures surface as `Laravel\Ai\Exceptions\AiException` (rate
 * limited, overloaded, connection), which is the signal `RunAiTask` retries on.
 */
interface AiDriver
{
    public function generate(AiRequest $request): AiResult;
}
