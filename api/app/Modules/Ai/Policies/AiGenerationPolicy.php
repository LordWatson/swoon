<?php

declare(strict_types=1);

namespace App\Modules\Ai\Policies;

use App\Models\User;
use App\Modules\Ai\Models\AiGeneration;

/**
 * A generation belongs to a couple, and only its live members may touch it.
 *
 * `hasActiveMember` rather than "was ever a member": once somebody unlinks,
 * the shared content stays with the closed couple where neither ex-partner can
 * read it (Accounts rule 4), and an AI generation is shared content.
 */
final class AiGenerationPolicy
{
    public function view(User $user, AiGeneration $generation): bool
    {
        return $this->belongsToTheirCouple($user, $generation);
    }

    /** Rate it. The rating itself stays private to whoever left it. */
    public function rate(User $user, AiGeneration $generation): bool
    {
        return $this->belongsToTheirCouple($user, $generation);
    }

    /**
     * Ask for a better one.
     *
     * Either partner may: a date plan belongs to both of them, and needing
     * your partner's permission to press "try again" would be absurd. It
     * counts against the couple's shared cap either way.
     */
    public function regenerate(User $user, AiGeneration $generation): bool
    {
        return $this->belongsToTheirCouple($user, $generation);
    }

    private function belongsToTheirCouple(User $user, AiGeneration $generation): bool
    {
        $couple = $user->couple();

        return $couple !== null
            && (int) $couple->getKey() === (int) $generation->couple_id
            && $couple->hasActiveMember($user);
    }
}
