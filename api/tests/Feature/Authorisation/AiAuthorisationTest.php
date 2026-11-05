<?php

declare(strict_types=1);

/*
 * The authorisation sweep for the AI module.
 *
 * Same shape as `AccountsAuthorisationTest`: driven off the real route table,
 * so an endpoint added here without a recorded decision about who may reach it
 * fails the suite rather than shipping. The pattern is deliberate repetition —
 * one sweep per module, each enumerating its own reality.
 */

use App\Modules\Ai\Models\AiGeneration;
use Illuminate\Support\Facades\Route;
use Tests\Authorisation\TwoCouples;

usingDatabase();

/**
 * What each AI route expects, by route name.
 *
 *   self  — acts on the caller's own singleton resource, with no id in the
 *           route, so it cannot be aimed at anyone else by construction. The
 *           negative case is a signed-in user who has no such resource.
 *   owned — takes an id, so it must refuse someone else's.
 *
 * @return array<string, array<string, mixed>>
 */
function aiCases(): array
{
    return [
        'ai.allowance' => [
            'kind' => 'self',
            'method' => 'get',
            'uri' => fn () => '/api/ai/allowance',
            // An allowance belongs to a couple, so a solo user has none.
            'status' => 404,
        ],
        'ai.generations.feedback' => [
            'kind' => 'owned',
            'method' => 'post',
            'uri' => fn (TwoCouples $w) => '/api/ai/generations/'.generationFor($w)->ulid.'/feedback',
            // 403, not 404: the ULID is unguessable, so there is nothing to
            // hide by pretending it does not exist, and 403 is the truth.
            'status' => 403,
        ],
        'ai.generations.regenerate' => [
            'kind' => 'owned',
            'method' => 'post',
            'uri' => fn (TwoCouples $w) => '/api/ai/generations/'.generationFor($w)->ulid.'/regenerate',
            'status' => 403,
        ],
    ];
}

/** One generation belonging to Alex and Sam, for the routes that take an id. */
function generationFor(TwoCouples $world): AiGeneration
{
    return AiGeneration::create([
        'couple_id' => $world->couple->getKey(),
        'module' => 'DatePlanner',
        'task' => 'date_plan',
        'prompt_name' => 'date_plan',
        'prompt_version' => 'v1',
        'payload' => ['partners' => [['first_name' => 'Ada']]],
        'output' => ['options' => []],
    ]);
}

/**
 * @return array<string, Illuminate\Routing\Route>
 */
function aiRoutes(): array
{
    $routes = [];

    foreach (Route::getRoutes()->getRoutesByName() as $name => $route) {
        if (str_contains($route->getActionName(), 'App\Modules\Ai\\')) {
            $routes[$name] = $route;
        }
    }

    return $routes;
}

it('has a decision recorded for every AI route', function (): void {
    $undecided = array_diff(array_keys(aiRoutes()), array_keys(aiCases()));

    expect($undecided)->toBe([], 'These AI routes have no authorisation case: '.implode(', ', $undecided));
});

it('does not list a route that no longer exists', function (): void {
    $stale = array_diff(array_keys(aiCases()), array_keys(aiRoutes()));

    expect($stale)->toBe([], 'These cases name routes that are gone: '.implode(', ', $stale));
});

it('refuses every AI route without a token', function (): void {
    $world = TwoCouples::make();

    foreach (aiCases() as $name => $case) {
        $this->{$case['method'].'Json'}(($case['uri'])($world), ['rating' => 'up'])
            ->assertUnauthorized("Route {$name} answered without a token");
    }
});

it('refuses another couple\'s generation', function (): void {
    $world = TwoCouples::make();

    foreach (aiCases() as $name => $case) {
        if ($case['kind'] !== 'owned') {
            continue;
        }

        $this->actingAs($world->outsider)
            ->{$case['method'].'Json'}(($case['uri'])($world), ['rating' => 'up'])
            ->assertStatus($case['status'], "Route {$name} let an outsider through");
    }
});

it('gives a signed-in user with no couple nothing to see', function (): void {
    $world = TwoCouples::make();

    foreach (aiCases() as $name => $case) {
        if ($case['kind'] !== 'self') {
            continue;
        }

        $this->actingAs($world->solo)
            ->{$case['method'].'Json'}(($case['uri'])($world))
            ->assertStatus($case['status'], "Route {$name} answered a user with no couple");
    }
});

it('keeps the allowance free of any id', function (): void {
    // Structural: there is no way to *ask* about another couple's allowance,
    // because the route takes no identifier.
    expect(aiRoutes()['ai.allowance']->uri())->not->toContain('{');
});

it('identifies a generation by its ULID, never its key', function (): void {
    // An auto-incrementing id in a URL is a way to count how much the product
    // is used and to walk other couples' rows.
    // `uri()` prints the binding field out, so the field itself is what has
    // to be asserted rather than the string.
    foreach (['ai.generations.feedback', 'ai.generations.regenerate'] as $name) {
        expect(aiRoutes()[$name]->bindingFieldFor('generation'))->toBe('ulid');
    }
});
