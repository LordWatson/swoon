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

use Illuminate\Support\Facades\Route;
use Tests\Authorisation\TwoCouples;

usingDatabase();

/**
 * What each AI route expects, by route name.
 *
 *   self — acts on the caller's own singleton resource, with no id in the
 *          route, so it cannot be aimed at anyone else by construction. The
 *          negative case is a signed-in user who has no such resource.
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
    ];
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
        $this->{$case['method'].'Json'}(($case['uri'])($world))
            ->assertUnauthorized("Route {$name} answered without a token");
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

it('keeps the AI routes free of any id', function (): void {
    // Structural: there is no way to *ask* about another couple's allowance,
    // because the route takes no identifier.
    foreach (aiRoutes() as $name => $route) {
        expect($route->uri())->not->toContain('{', "Route {$name} takes an identifier");
    }
});

it('exposes no endpoint that reaches a model', function (): void {
    // The module's standing guarantee: the model is reached only from a queued
    // job. Every route here is about a couple's own allowance or their own
    // past generations, so none of them may be a write that generates.
    foreach (aiRoutes() as $name => $route) {
        expect(array_intersect($route->methods(), ['GET', 'HEAD']))
            ->not->toBe([], "Route {$name} is a write; if it generates, it must dispatch a job and say so here");
    }
});
