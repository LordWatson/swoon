<?php

declare(strict_types=1);

namespace App\Modules\Ai\Http\Resources;

use App\Modules\Accounts\Models\Couple;
use App\Modules\Ai\Support\CapVerdict;
use App\Modules\Billing\Support\Entitlement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What the couple has left this month, so the app can say so before they tap.
 *
 * Being told "that's your date plans for this month" **after** filling in a
 * form is a worse experience than seeing "1 left" next to the button, and the
 * numbers come from the same guard either way — so the screen and the refusal
 * cannot disagree.
 *
 * The refusal copy is included per task, already written, so the app never
 * composes its own version of a message that has to be kind.
 *
 * @property-read array{couple: Couple, allowance: array<string, CapVerdict>} $resource
 */
final class AllowanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, CapVerdict> $allowance */
        $allowance = $this->resource['allowance'];

        $tasks = [];

        foreach ($allowance as $task => $verdict) {
            $tasks[$task] = [
                'used' => $verdict->used,
                'limit' => $verdict->limit,
                'remaining' => $verdict->remaining(),
                'available' => $verdict->allowed,
                'resets_at' => $verdict->resetsAt->toIso8601String(),
                // Null when there is nothing to explain.
                'message' => $verdict->message,
            ];
        }

        return [
            'tier' => Entitlement::tierFor($this->resource['couple']),
            'tasks' => $tasks,
        ];
    }
}
