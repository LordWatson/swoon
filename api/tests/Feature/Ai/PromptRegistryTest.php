<?php

declare(strict_types=1);

/**
 * The prompt registry.
 *
 * Two rules carry the weight. A new file changes nothing until config pins it,
 * so a prompt edit cannot reach a couple by being saved. And every hole in a
 * prompt is an exception, because a prompt sent with an empty slot comes back
 * with a confident answer to a question nobody asked.
 */

use App\Modules\Ai\Support\PromptRegistry;

/**
 * Point the registry at the fixture prompts rather than the real ones.
 *
 * @param  array<string, string>  $active
 */
function usingFixturePrompts(array $active = []): PromptRegistry
{
    config([
        'ai.prompts.path' => base_path('tests/Fixtures/Ai/prompts'),
        'ai.prompts.active' => $active,
    ]);

    return app(PromptRegistry::class);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function exampleVariables(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Ada',
        'partner_name' => 'Grace',
        'days' => 214,
        'has_car' => true,
        'recent_dates' => ['Bread and the moors', 'That terrible film'],
    ], $overrides);
}

describe('version pinning', function (): void {
    it('uses the pinned version even though a newer file sits beside it', function (): void {
        $prompt = usingFixturePrompts(['example' => 'v1'])->resolve('example', exampleVariables());

        expect($prompt->version)->toBe('v1')
            ->and($prompt->text)->toContain('You are helping Ada and Grace')
            ->and($prompt->text)->not->toContain('A newer version');
    });

    it('switches only when config says so', function (): void {
        $prompt = usingFixturePrompts(['example' => 'v2'])->resolve('example', ['first_name' => 'Ada']);

        expect($prompt->version)->toBe('v2')
            ->and($prompt->text)->toContain('A newer version');
    });

    it('refuses a prompt nobody pinned', function (): void {
        expect(fn (): mixed => usingFixturePrompts()->resolve('example'))
            ->toThrow(InvalidArgumentException::class, 'has no pinned version');
    });

    it('refuses a pin with no file behind it', function (): void {
        expect(fn (): mixed => usingFixturePrompts(['example' => 'v9'])->resolve('example'))
            ->toThrow(RuntimeException::class, 'pinned at [v9]');
    });

    it('refuses a version that is not a version', function (): void {
        expect(fn (): mixed => usingFixturePrompts(['example' => 'latest'])->resolve('example'))
            ->toThrow(InvalidArgumentException::class, 'has no pinned version');
    });

    it('carries the reference that goes on a usage row', function (): void {
        expect(usingFixturePrompts(['plain' => 'v1'])->resolve('plain')->reference())->toBe('plain/v1');
    });
});

describe('slots', function (): void {
    it('fills every kind of value', function (): void {
        $text = usingFixturePrompts(['example' => 'v1'])->resolve('example', exampleVariables())->text;

        expect($text)->toContain('together 214 days')
            // "yes", not PHP's "1" — and never "" for false.
            ->and($text)->toContain('Car available: yes')
            ->and($text)->toContain('"Bread and the moors"');
    });

    it('writes false as a word rather than nothing', function (): void {
        $text = usingFixturePrompts(['example' => 'v1'])
            ->resolve('example', exampleVariables(['has_car' => false]))->text;

        expect($text)->toContain('Car available: no');
    });

    it('throws on a slot with no value', function (): void {
        $variables = exampleVariables();
        unset($variables['partner_name']);

        expect(fn (): mixed => usingFixturePrompts(['example' => 'v1'])->resolve('example', $variables))
            ->toThrow(InvalidArgumentException::class, 'slot [partner_name] with no value');
    });

    it('throws on null rather than inventing a blank', function (): void {
        expect(fn (): mixed => usingFixturePrompts(['example' => 'v1'])
            ->resolve('example', exampleVariables(['partner_name' => null])))
            ->toThrow(InvalidArgumentException::class, 'null is rejected on purpose');
    });

    it('throws on a value the prompt has no slot for', function (): void {
        // A value the prompt ignores means the task's allow-list and the
        // prompt have drifted, and a reviewer cannot tell which is right.
        expect(fn (): mixed => usingFixturePrompts(['example' => 'v1'])
            ->resolve('example', exampleVariables(['gift_vault' => 'a necklace'])))
            ->toThrow(InvalidArgumentException::class, 'no slot for: gift_vault');
    });

    it('leaves braces inside a value as text', function (): void {
        // Someone writes "{{ partner_name }}" in a note. It must not become a
        // way to reach another variable.
        $text = usingFixturePrompts(['example' => 'v1'])
            ->resolve('example', exampleVariables(['first_name' => '{{ partner_name }}']))->text;

        expect($text)->toContain('helping {{ partner_name }} and Grace');
    });

    it('accepts a prompt with no slots and no values', function (): void {
        expect(usingFixturePrompts(['plain' => 'v1'])->resolve('plain')->text)
            ->toContain('no slots at all');
    });
});

it('has a file for every prompt the real config pins', function (): void {
    // The gate. A pinned prompt with no file is a promise the code cannot
    // keep, and it would only surface when a couple triggered the feature.
    $registry = app(PromptRegistry::class);

    $missing = [];

    foreach (array_keys((array) config('ai.prompts.active')) as $name) {
        $path = $registry->pathFor((string) $name);

        if (! is_file($path)) {
            $missing[(string) $name] = $path;
        }
    }

    expect($missing)->toBe([]);
});
