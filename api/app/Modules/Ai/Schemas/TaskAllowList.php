<?php

declare(strict_types=1);

namespace App\Modules\Ai\Schemas;

use InvalidArgumentException;

/**
 * Exactly what each AI task is allowed to see, in one readable place.
 *
 * An **allow-list**, never a deny-list, and that is the whole design. A
 * deny-list has to be updated every time a private column is added, by
 * somebody who happens to remember this file exists. An allow-list excludes
 * a new column by not mentioning it, which is the behaviour we want from the
 * person who forgets.
 *
 * Paths are dotted, with `*` for a position in a list:
 *
 *     partners.*.first_name
 *     recent_dates.*.title
 *
 * **Every entry names a leaf.** Naming a container - `partners.*.answers` for
 * "all the answers" - would quietly include whatever is added inside it later,
 * which is exactly the failure this file exists to prevent, so `PrivacyFilter`
 * refuses to do it. A list of plain values (`dietary_needs`) is fine: it can
 * grow longer, but it cannot grow new fields.
 *
 * The filter also refuses a task that is not listed here, so there is no route
 * to the model for a task nobody has decided the inputs for.
 */
final class TaskAllowList
{
    /**
     * @return list<string>
     */
    public static function for(string $task): array
    {
        return match ($task) {
            'safety_screen' => self::SAFETY_SCREEN,
            'date_plan' => self::DATE_PLAN,

            default => throw new InvalidArgumentException(
                "AI task [{$task}] has no declared input allow-list. Add it to ".self::class.'.'
            ),
        };
    }

    /**
     * The screen sees the words as written, private ones included.
     *
     * This is the one task that is *meant* to receive text nobody else may
     * see, and it has to: a locked love note or a private moment is exactly
     * where somebody might say something that needs a signpost, and a screen
     * that skipped private text would skip the most important cases.
     *
     * It is safe because of what the task is, not because of what it is given:
     * it returns a classification and nothing else, nothing about it is
     * persisted or logged, and its output never reaches the other partner
     * (ADR-025).
     */
    private const SAFETY_SCREEN = ['text'];

    /**
     * Date planning: two partners' answers, and nothing about either of them
     * that the other has not already chosen to share.
     *
     * Note `outcode` and not `postcode`. The planner never receives a full
     * postcode, a street, or coordinates - a relationship app must not become
     * a way to work out where somebody is.
     *
     * Dietary and accessibility needs are here on purpose, and are the one
     * category of personal detail that is always included: a date suggestion
     * that ignores them is worse than no suggestion.
     *
     * Declared now, used from Phase 3. The prompt is not written yet; the
     * list is, so the privacy tests exist before the feature does.
     */
    private const DATE_PLAN = [
        'partners.*.first_name',
        'partners.*.dietary_needs',
        'partners.*.accessibility_needs',
        'partners.*.answers.when',
        'partners.*.answers.area',
        'partners.*.answers.outcode',
        'partners.*.answers.duration_minutes',
        'partners.*.answers.has_car',
        'partners.*.answers.budget_pence',
        'partners.*.answers.vibe',
        'partners.*.answers.romance',
        'partners.*.answers.energy',
        'partners.*.answers.notes',
        'recent_dates.*.title',
        'recent_dates.*.vibe',
        'bucket_list.*.title',
    ];
}
