<?php

declare(strict_types=1);

/**
 * The privacy filter.
 *
 * One test per private field, by name, so the list cannot rot quietly: if
 * somebody widens the allow-list, the field's own test is what fails, and the
 * failure says which field.
 *
 * The test that matters most is the last one. Every other test here knows what
 * it is looking for; that one proves the filter is right about things nobody
 * has thought of yet, which is the only property that survives the project
 * growing.
 */

use App\Modules\Ai\Schemas\TaskAllowList;
use App\Modules\Ai\Support\PrivacyFilter;
use Illuminate\Support\Facades\Schema;

// For the last test, which reads the real `users` columns rather than a list
// somebody remembered to keep up to date.
usingDatabase();

function filter(): PrivacyFilter
{
    return new PrivacyFilter;
}

/**
 * A date-planning payload with every allow-listed field filled in, plus
 * whatever a caller passes as `$leaks`.
 *
 * @param  array<string, mixed>  $leaks  Attached to the first partner, where a `users` column would really sit.
 * @param  array<string, mixed>  $top  Attached at the top level.
 * @return array<string, mixed>
 */
function planPayload(array $leaks = [], array $top = []): array
{
    return [
        'partners' => [
            [
                'first_name' => 'Ada',
                'dietary_needs' => ['vegetarian'],
                'accessibility_needs' => ['step-free'],
                'answers' => [
                    'when' => 'Saturday evening',
                    'area' => 'Leeds',
                    'outcode' => 'LS6',
                    'duration_minutes' => 180,
                    'has_car' => true,
                    'budget_pence' => 6000,
                    'vibe' => 'cosy',
                    'romance' => 4,
                    'energy' => 2,
                    'notes' => 'nothing too loud',
                ],
                ...$leaks,
            ],
            [
                'first_name' => 'Grace',
                'dietary_needs' => [],
                'accessibility_needs' => [],
                'answers' => [
                    'when' => 'Saturday evening',
                    'area' => 'Leeds',
                    'outcode' => 'LS6',
                    'duration_minutes' => 120,
                    'has_car' => false,
                    'budget_pence' => 4000,
                    'vibe' => 'lively',
                    'romance' => 5,
                    'energy' => 4,
                    'notes' => '',
                ],
            ],
        ],
        'recent_dates' => [['title' => 'Bread and the moors', 'vibe' => 'cosy']],
        'bucket_list' => [['title' => 'Sleeper train to Scotland']],
        ...$top,
    ];
}

/**
 * Everything the filter kept, as dotted paths, for a plain-English assertion.
 *
 * @param  array<array-key, mixed>  $payload
 * @return list<string>
 */
function keptPaths(array $payload, string $prefix = ''): array
{
    $paths = [];
    $isList = array_is_list($payload);

    foreach ($payload as $key => $value) {
        $segment = $isList ? '*' : (string) $key;
        $path = $prefix === '' ? $segment : $prefix.'.'.$segment;

        if (is_array($value) && ! array_is_list($value)) {
            $paths = [...$paths, ...keptPaths($value, $path)];

            continue;
        }

        if (is_array($value) && array_is_list($value) && ($value[0] ?? null) !== null && is_array($value[0])) {
            $paths = [...$paths, ...keptPaths($value, $path)];

            continue;
        }

        $paths[] = $path;
    }

    return array_values(array_unique($paths));
}

describe('a private field cannot reach a payload', function (): void {
    it('drops a gift vault entry', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'gift_vault' => [['item' => 'a necklace', 'for' => 'her birthday']],
        ]));

        expect($filtered)->not->toHaveKey('gift_vault')
            ->and(json_encode($filtered))->not->toContain('necklace');
    });

    it('drops an individual check-in answer', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'check_in' => [['question' => 'How close do you feel?', 'answer' => 'Not very, lately']],
        ]));

        expect($filtered)->not->toHaveKey('check_in')
            ->and(json_encode($filtered))->not->toContain('Not very');
    });

    it('drops a private moment note', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'moments' => [['note' => 'the thing I have never told her', 'is_private' => true]],
        ]));

        expect(json_encode($filtered))->not->toContain('never told her');
    });

    it('drops a private love-map entry', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'love_map' => [['question' => 'biggest fear', 'answer' => 'being left', 'is_private' => true]],
        ]));

        expect(json_encode($filtered))->not->toContain('being left');
    });

    it('drops a locked love note', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'love_notes' => [['body' => 'read this when you miss me', 'unlocks_at' => '2027-01-01']],
        ]));

        expect(json_encode($filtered))->not->toContain('miss me');
    });

    it("drops the partner's un-revealed answers", function (): void {
        // Before both partners have submitted, neither may see the other's.
        // The reveal gate is server-side, and so is this.
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'partner_unrevealed_answers' => ['vibe' => 'a surprise', 'notes' => 'do not tell her'],
        ]));

        expect(json_encode($filtered))->not->toContain('do not tell her');
    });

    it('drops an email address', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(leaks: ['email' => 'ada@example.com']));

        expect(json_encode($filtered))->not->toContain('ada@example.com')
            ->and(json_encode($filtered))->not->toContain('example.com');
    });

    it('drops a full postcode, and keeps only the outcode', function (): void {
        // The planner stores and sends the outcode and nothing finer. This app
        // must not become a way to work out where somebody is.
        $filtered = filter()->apply('date_plan', planPayload(leaks: ['postcode' => 'LS6 2AB']));

        expect(json_encode($filtered))->not->toContain('LS6 2AB')
            ->and(json_encode($filtered))->toContain('LS6');
    });

    it('drops a shipping address', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(top: [
            'shipping_address' => ['line_1' => '14 Example Street', 'postcode' => 'LS6 2AB'],
        ]));

        expect(json_encode($filtered))->not->toContain('Example Street');
    });

    it('drops coordinates', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(leaks: [
            'latitude' => 53.8008,
            'longitude' => -1.5491,
        ]));

        expect(json_encode($filtered))->not->toContain('53.8')
            ->and(json_encode($filtered))->not->toContain('1.54');
    });

    it('drops a password hash and a token', function (): void {
        $filtered = filter()->apply('date_plan', planPayload(leaks: [
            'password' => 'not-a-real-hash',
            'remember_token' => 'not-a-real-token',
        ]));

        expect(json_encode($filtered))->not->toContain('not-a-real-hash')
            ->and(json_encode($filtered))->not->toContain('not-a-real-token');
    });
});

describe('what it keeps', function (): void {
    it('keeps exactly the allow-list and nothing more', function (): void {
        expect(keptPaths(filter()->apply('date_plan', planPayload())))
            ->toEqualCanonicalizing(TaskAllowList::for('date_plan'));
    });

    it('keeps dietary and accessibility needs, which are never dropped', function (): void {
        // The one category of personal detail that is always included: a date
        // suggestion that ignores them is worse than no suggestion.
        $filtered = filter()->apply('date_plan', planPayload());

        expect($filtered['partners'][0]['dietary_needs'])->toBe(['vegetarian'])
            ->and($filtered['partners'][0]['accessibility_needs'])->toBe(['step-free']);
    });

    it('keeps both partners even when one has nothing to say', function (): void {
        // Dropping an empty item would change how many people the payload
        // describes, and the model cannot tell that two became one.
        $payload = planPayload();
        $payload['partners'][1] = ['email' => 'grace@example.com'];

        $filtered = filter()->apply('date_plan', $payload);

        expect($filtered['partners'])->toHaveCount(2)
            ->and($filtered['partners'][1])->toBe([]);
    });

    it('keeps a list a list, so positions do not become keys', function (): void {
        expect(array_is_list(filter()->apply('date_plan', planPayload())['partners']))->toBeTrue();
    });

    it('lets the safety screen see the words as written', function (): void {
        // Including private ones, on purpose: a locked love note is exactly
        // where somebody might say something that needs a signpost.
        $text = 'the thing I have never told her';

        expect(filter()->apply('safety_screen', ['text' => $text]))->toBe(['text' => $text]);
    });

    it('drops anything else from a safety screen', function (): void {
        expect(filter()->apply('safety_screen', ['text' => 'hello', 'user_id' => 7, 'email' => 'a@b.com']))
            ->toBe(['text' => 'hello']);
    });
});

describe('the allow-list itself', function (): void {
    it('refuses a task nobody has decided the inputs for', function (): void {
        // No route to the model for an undeclared task, so "we forgot to
        // write the allow-list" cannot become "it sent everything".
        expect(fn (): array => filter()->apply('weekly_recap', ['anything' => 'at all']))
            ->toThrow(InvalidArgumentException::class, 'has no declared input allow-list');
    });

    it('refuses an allow-listed path that turns out to hold an object', function (): void {
        // `partners.*.dietary_needs` is allow-listed as a list of values. Hand
        // it a list of objects and it stops, because allowing a container
        // would include whatever is added inside it later - which is the whole
        // failure this class exists to prevent.
        $payload = planPayload();
        $payload['partners'][0]['dietary_needs'] = [['need' => 'vegetarian', 'because' => 'a private reason']];

        expect(fn (): array => filter()->apply('date_plan', $payload))
            ->toThrow(InvalidArgumentException::class, 'holds an object');
    });

    it('names no full postcode, street, or coordinate anywhere', function (): void {
        // A gate on the list rather than on a payload: these must never be
        // allow-listed for any task, however convenient it looks one day.
        $forbidden = ['postcode', 'latitude', 'longitude', 'address', 'line_1', 'email', 'phone', 'password', 'token'];

        foreach (['safety_screen', 'date_plan'] as $task) {
            foreach (TaskAllowList::for($task) as $path) {
                foreach ($forbidden as $word) {
                    expect($path)->not->toContain($word);
                }
            }
        }
    });
});

it('excludes a column nobody has heard of yet', function (): void {
    // The test that outlives the others. A column added to `users` next year,
    // by somebody who has never opened this file, must not reach a prompt
    // because they forgot to exclude it. Nothing excludes it: the allow-list
    // simply does not mention it.
    $payload = planPayload(leaks: [
        'a_column_added_next_year' => 'something nobody has thought about',
        'mood_tracking_notes' => 'very private indeed',
    ]);

    $filtered = filter()->apply('date_plan', $payload);

    expect(json_encode($filtered))->not->toContain('nobody has thought about')
        ->and(json_encode($filtered))->not->toContain('very private indeed');
});

it('drops every column of the real users table that is not allow-listed', function (): void {
    // Driven off the actual schema, so this grows with the table rather than
    // with somebody's memory of it.
    $columns = Schema::getColumnListing('users');

    $payload = planPayload(leaks: array_combine(
        $columns,
        array_map(fn (string $column): string => 'value-of-'.$column, $columns),
    ));

    $filtered = json_encode(filter()->apply('date_plan', $payload));

    // A `users` column that *is* allow-listed is supposed to survive -
    // dietary_needs and accessibility_needs are real columns and are
    // deliberately always included.
    $allowedAtPartnerLevel = array_map(
        fn (string $path): string => substr($path, strlen('partners.*.')),
        array_filter(
            TaskAllowList::for('date_plan'),
            fn (string $path): bool => str_starts_with($path, 'partners.*.') && substr_count($path, '.') === 2,
        ),
    );

    $leaked = array_values(array_filter(
        $columns,
        fn (string $column): bool => ! in_array($column, $allowedAtPartnerLevel, true)
            && str_contains((string) $filtered, 'value-of-'.$column),
    ));

    expect($leaked)->toBe([])
        // And the guard on the guard: if the payload were not actually built
        // from the real columns, this test would pass by checking nothing.
        ->and($columns)->toContain('email', 'password');
});
