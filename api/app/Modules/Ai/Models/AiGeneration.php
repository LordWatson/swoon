<?php

declare(strict_types=1);

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * What a couple was actually shown, exactly as they saw it.
 *
 * It records the prompt version that produced it, which is the reason a
 * released prompt file is never edited in place: change `v1.md` and every row
 * pointing at `v1` becomes a lie about its own history.
 *
 * A regenerate **supersedes** rather than overwrites. The old row stays with
 * `superseded_by` set, so "what did we show them in March" has an answer even
 * after they asked for something better in April.
 *
 * There is no `safety_outcome` column: a row only exists when the output was
 * shown, which means the screen said safe, so it could hold one value.
 *
 * @property Carbon $created_at
 * @property array<string, mixed> $output
 */
#[Fillable([
    'couple_id', 'module', 'task', 'prompt_name', 'prompt_version',
    'payload_hash', 'output',
])]
final class AiGeneration extends Model
{
    protected static function booted(): void
    {
        // Allocated here rather than in the Action, so no code path can
        // create a generation without a public identifier.
        self::creating(function (AiGeneration $generation): void {
            $generation->ulid ??= (string) Str::ulid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'output' => 'array',
        ];
    }

    /**
     * Point this generation at the one that replaced it.
     *
     * A write to the *old* row, never a write over it. The replacement is a
     * new row with its own id, its own prompt version and its own output.
     */
    public function supersede(self $replacement): void
    {
        $this->forceFill(['superseded_by' => $replacement->getKey()])->save();
    }

    public function isSuperseded(): bool
    {
        return $this->superseded_by !== null;
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superseded_by');
    }

    /**
     * Ratings, which are private to whoever left them.
     *
     * @return HasMany<AiFeedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(AiFeedback::class);
    }

    /**
     * Record what one person thought of this output.
     *
     * `user_id` is set from a `User`, never from request input, and is not
     * fillable — one method rather than a rule each endpoint has to remember,
     * because "rate as somebody else" is the obvious thing to try.
     */
    public function rateBy(User $user, string $rating, ?string $note = null): AiFeedback
    {
        $feedback = new AiFeedback(['rating' => $rating, 'note' => $note]);

        $feedback->forceFill([
            'ai_generation_id' => $this->getKey(),
            'user_id' => $user->getKey(),
        ])->save();

        return $feedback;
    }
}
