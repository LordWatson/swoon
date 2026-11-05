<?php

declare(strict_types=1);

namespace App\Modules\Ai\Models;

use App\Modules\Ai\Support\AiOutcome;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row per AI call, whatever happened to it.
 *
 * It holds counts and never content: tokens, cost, how long it took, which
 * task and which prompt version. Nothing in a row could tell you what two
 * people wrote, which is what makes it safe to keep for as long as the
 * accounting needs it.
 *
 * A `safety_tripped` row carries no couple and no user (ADR-027). `UsageLogger`
 * is the only thing that writes these, so that is enforced in one place.
 *
 * @property AiOutcome $outcome
 * @property Carbon $created_at
 */
#[Fillable([
    'couple_id', 'user_id', 'module', 'task', 'prompt_name', 'prompt_version',
    'model', 'input_tokens', 'output_tokens', 'cost_millipence', 'outcome',
    'duration_ms',
])]
final class AiUsage extends Model
{
    /** Singular on purpose: it is a mass noun, and the table is `ai_usage`. */
    protected $table = 'ai_usage';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'outcome' => AiOutcome::class,
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'cost_millipence' => 'integer',
            'duration_ms' => 'integer',
        ];
    }
}
