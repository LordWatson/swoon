<?php

declare(strict_types=1);

namespace App\Modules\Ai\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * What one person thought of one AI output.
 *
 * **Private to the rater.** A partner never sees that the other thumbed-down
 * a date plan — the whole point of a rating is that it is honest, and it stops
 * being honest the moment it is shared. Enforced by the policy and the
 * resource when the endpoints land (P2.T08), and by the unique index here,
 * which keeps one opinion per person rather than letting a double tap read as
 * two people disliking something.
 */
#[Fillable(['rating', 'note'])]
final class AiFeedback extends Model
{
    protected $table = 'ai_feedback';

    /**
     * @return BelongsTo<AiGeneration, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
