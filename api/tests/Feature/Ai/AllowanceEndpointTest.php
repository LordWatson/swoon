<?php

declare(strict_types=1);

/**
 * `GET /api/ai/allowance`.
 *
 * The screen and the refusal come from the same guard, so they cannot
 * disagree: being told "that's your date plans for this month" after filling
 * in a form is worse than seeing "1 left" beside the button.
 */

use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\Authorisation\TwoCouples;

usingDatabase();

it('says what is left, per task', function (): void {
    $world = TwoCouples::make();

    $this->actingAs($world->alex)->getJson('/api/ai/allowance')
        ->assertOk()
        ->assertJsonPath('tier', 'spark')
        ->assertJsonPath('tasks.date_plan.limit', 1)
        ->assertJsonPath('tasks.date_plan.used', 0)
        ->assertJsonPath('tasks.date_plan.remaining', 1)
        ->assertJsonPath('tasks.date_plan.available', true)
        ->assertJsonPath('tasks.date_plan.message', null);
});

it('counts a generation the couple has already had', function (): void {
    $world = TwoCouples::make();

    (new UsageLogger)->record(
        outcome: AiOutcome::Ok,
        module: 'DatePlanner',
        task: 'date_plan',
        coupleId: $world->couple->getKey(),
    );

    $this->actingAs($world->sam)->getJson('/api/ai/allowance')
        ->assertOk()
        ->assertJsonPath('tasks.date_plan.used', 1)
        ->assertJsonPath('tasks.date_plan.remaining', 0)
        ->assertJsonPath('tasks.date_plan.available', false);
});

it('carries the refusal copy, already written', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-11-14 12:00:00', 'Europe/London'));

    $world = TwoCouples::make();

    (new UsageLogger)->record(
        outcome: AiOutcome::Ok,
        module: 'DatePlanner',
        task: 'date_plan',
        coupleId: $world->couple->getKey(),
    );

    // The app never composes its own version of a message that has to be kind.
    $this->actingAs($world->alex)->getJson('/api/ai/allowance')
        ->assertOk()
        ->assertJsonPath('tasks.date_plan.message', "That's your date plans for this month 💝 They come back on 1 December.");

    Carbon::setTestNow();
});

it('shows a feature the tier does not include as a nought, with its own words', function (): void {
    $world = TwoCouples::make();

    $this->actingAs($world->alex)->getJson('/api/ai/allowance')
        ->assertOk()
        ->assertJsonPath('tasks.weekly_recap.limit', 0)
        ->assertJsonPath('tasks.weekly_recap.available', false)
        ->assertJsonPath('tasks.weekly_recap.message', 'Weekly recaps come with Together 💛');
});

it('lists the capped tasks and not the internal ones', function (): void {
    // `safety_screen` has no allowance, and showing one would invite a
    // question about something a couple never asks for.
    $world = TwoCouples::make();

    $tasks = $this->actingAs($world->alex)->getJson('/api/ai/allowance')->json('tasks');

    expect(array_keys($tasks))->toEqualCanonicalizing(array_keys((array) config('ai.caps.spark')))
        ->and($tasks)->not->toHaveKey('safety_screen');
});

it('gives each couple their own numbers', function (): void {
    $world = TwoCouples::make();

    (new UsageLogger)->record(
        outcome: AiOutcome::Ok,
        module: 'DatePlanner',
        task: 'date_plan',
        coupleId: $world->couple->getKey(),
    );

    $this->actingAs($world->outsider)->getJson('/api/ai/allowance')
        ->assertOk()
        ->assertJsonPath('tasks.date_plan.used', 0);
});

it('has no allowance for somebody with no couple', function (): void {
    // An allowance is a property of a couple, not of a person.
    $world = TwoCouples::make();

    $this->actingAs($world->solo)->getJson('/api/ai/allowance')->assertNotFound();
});

it('needs a token', function (): void {
    $this->getJson('/api/ai/allowance')->assertUnauthorized();
});

it('takes no identifier, so it cannot be aimed at another couple', function (): void {
    expect(Route::getRoutes()->getByName('ai.allowance')?->uri())
        ->not->toContain('{');
});
