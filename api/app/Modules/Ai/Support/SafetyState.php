<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

/**
 * What the safety screen concluded.
 *
 * Three states, not two, because a concern and a broken guard are different
 * things to a person. A concern means we are fairly sure something hard is
 * happening, and the right response is the signpost. A broken guard means we
 * do not know, and the right response is nothing at all — handing over an
 * unscreened date plan would be the one outcome this module exists to prevent,
 * and showing a crisis signpost to someone who was choosing a restaurant would
 * be its own small harm.
 */
enum SafetyState: string
{
    /** The screen ran and found nothing. The generation may go ahead. */
    case Safe = 'safe';

    /** The screen found something, or could not tell. Signpost only. */
    case Concerning = 'concerning';

    /** The screen itself failed. Abandon the generation; show nothing. */
    case Unusable = 'unusable';
}
