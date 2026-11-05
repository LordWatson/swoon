<?php

declare(strict_types=1);

/**
 * `swoon:ai-eval`.
 *
 * The dry run is what CI gets: it proves every case in a battery still fits
 * its prompt, costs nothing and calls nothing. The live run is the only thing
 * that measures a prompt, and it is fenced off behind four gates, one of which
 * is "never from a test".
 */

use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Schemas\TaskSchemas;

/**
 * Every prompt with a battery, so adding one gets covered without asking.
 *
 * @return list<string>
 */
function batteries(): array
{
    $directories = glob(__DIR__.'/../../Fixtures/Ai/evals/*', GLOB_ONLYDIR) ?: [];

    return array_map(basename(...), $directories);
}

it('has a battery directory for a prompt that really exists', function (): void {
    // A battery for a prompt nobody wrote would pass forever while measuring
    // nothing.
    foreach (batteries() as $prompt) {
        expect(fn (): Closure => TaskSchemas::for($prompt))->not->toThrow(InvalidArgumentException::class);
        expect(config('ai.prompts.active.'.$prompt))->not->toBeNull("[{$prompt}] has a battery but no pinned prompt");
    }
});

it('finds the batteries it means to run', function (): void {
    // If the glob stopped matching, every test below would pass by looping
    // over nothing.
    expect(batteries())->toContain('safety_screen');
});

it('runs every battery dry, calling nothing and spending nothing', function (): void {
    foreach (batteries() as $prompt) {
        $this->artisan('swoon:ai-eval', ['prompt' => $prompt])
            ->expectsOutputToContain('Nothing was called and nothing was spent')
            ->assertSuccessful();
    }

    // The point of the dry run. CI gets the rot-detection for free.
    expect(app(FakeDriver::class)->callCount())->toBe(0);
});

it('renders every case, which is what the dry run is for', function (): void {
    // A prompt that gains a slot breaks every case that does not supply it.
    // That is the failure worth catching without spending anything.
    $this->artisan('swoon:ai-eval', ['prompt' => 'safety_screen'])
        ->expectsOutputToContain('77 cases')
        ->assertSuccessful();
});

it('fails loudly when the prompt is not pinned', function (): void {
    config(['ai.prompts.active' => []]);

    expect(fn (): int => $this->artisan('swoon:ai-eval', ['prompt' => 'safety_screen'])->run())
        ->toThrow(InvalidArgumentException::class, 'has no pinned version');
});

it('says so when a prompt has no battery at all', function (): void {
    $this->artisan('swoon:ai-eval', ['prompt' => 'date_plan'])
        ->expectsOutputToContain('No eval battery for [date_plan]')
        ->assertFailed();
});

describe('a live run', function (): void {
    it('refuses from a test, whatever else is set', function (): void {
        // Not a judgement call: a test that spends money is a failure
        // whatever it was trying to prove.
        config(['ai.driver' => 'anthropic', 'ai.providers.anthropic.key' => 'not-a-real-key']);

        $this->artisan('swoon:ai-eval', ['prompt' => 'safety_screen', '--live' => true])
            ->expectsOutputToContain('cannot run from a test')
            ->assertFailed();

        expect(app(FakeDriver::class)->callCount())->toBe(0);
    });

    it('is not what happens by default', function (): void {
        // The flag is the first gate. Without it there is no path to a call.
        $this->artisan('swoon:ai-eval', ['prompt' => 'safety_screen'])->assertSuccessful();

        expect(app(FakeDriver::class)->callCount())->toBe(0);
    });
});

it('samples a battery when asked, for a cheap look', function (): void {
    $this->artisan('swoon:ai-eval', ['prompt' => 'safety_screen', '--limit' => 3])
        ->expectsOutputToContain('(3 cases)')
        ->assertSuccessful();
});
