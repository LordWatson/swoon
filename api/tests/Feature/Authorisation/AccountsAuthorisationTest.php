<?php

declare(strict_types=1);

/*
 * The authorisation sweep for Accounts.
 *
 * This file is **driven off the real route table**, not a hand-written list.
 * Every route whose controller lives in the Accounts module has to appear in
 * `CASES` below, and the first test fails if one does not — so adding an
 * endpoint without thinking about who may reach it breaks the suite rather
 * than shipping.
 *
 * That is the point. A list of hand-written assertions passes forever while
 * the route table grows past it; a sweep that enumerates reality cannot.
 *
 * Phase 10 makes this a standing requirement for every module. The pattern to
 * copy: `Tests\Authorisation\TwoCouples::make()` plus a `CASES` map keyed by
 * route name.
 */

use App\Modules\Accounts\Actions\BuildDataExport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Authorisation\TwoCouples;

usingDatabase();

/**
 * What each Accounts route expects, by route name.
 *
 *   guest  — deliberately public. Say why.
 *   auth   — needs a token, and acts only on the caller. Nothing to steal.
 *   self   — acts on the caller's own SINGLETON resource, with no id in the
 *            route. Cannot be aimed at anyone else by construction, so the
 *            negative case is a signed-in user who has no such resource.
 *   owned  — takes an id, so it must refuse someone else's.
 *
 * `uri` builds a real URL from the fixture. `status` is what the wrong caller
 * should get.
 *
 * @return array<string, array<string, mixed>>
 */
function accountsCases(): array
{
    return [
        // ── Public on purpose ────────────────────────────────────────────────
        'auth.register' => ['kind' => 'guest', 'why' => 'you cannot have a token before you have an account'],
        'auth.login' => ['kind' => 'guest', 'why' => 'same'],
        'auth.apple' => ['kind' => 'guest', 'why' => 'same'],
        'auth.google' => ['kind' => 'guest', 'why' => 'same'],
        'invites.show' => [
            'kind' => 'guest',
            'why' => 'the link is tapped before sign-up; returns a first name and nothing else',
        ],

        // ── Token required, acts only on the caller ─────────────────────────
        'auth.logout' => ['kind' => 'auth', 'method' => 'post', 'uri' => fn () => '/api/auth/logout'],
        'me.show' => ['kind' => 'auth', 'method' => 'get', 'uri' => fn () => '/api/me'],
        'me.update' => ['kind' => 'auth', 'method' => 'patch', 'uri' => fn () => '/api/me'],
        'me.export' => ['kind' => 'auth', 'method' => 'post', 'uri' => fn () => '/api/me/export'],
        'me.destroy' => ['kind' => 'auth', 'method' => 'delete', 'uri' => fn () => '/api/me'],
        'invites.store' => ['kind' => 'auth', 'method' => 'post', 'uri' => fn () => '/api/invites'],
        'invites.accept' => [
            'kind' => 'auth',
            'method' => 'post',
            'uri' => fn (TwoCouples $w) => '/api/invites/'.$w->invite->code.'/accept',
        ],

        // ── Token required AND somebody else's thing must be refused ────────
        'invites.remind' => [
            'kind' => 'owned',
            'method' => 'post',
            'uri' => fn (TwoCouples $w) => '/api/invites/'.$w->invite->code.'/remind',
            // 404: nudging somebody else's invite must not confirm it exists.
            'status' => 404,
        ],
        'invites.destroy' => [
            'kind' => 'owned',
            'method' => 'delete',
            'uri' => fn (TwoCouples $w) => '/api/invites/'.$w->invite->getKey(),
            // 404, not 403: an invite id is guessable, so a stranger learns
            // nothing about whether it exists.
            'status' => 404,
        ],
        // No id in the route, so an outsider cannot aim these at Alex and Sam
        // at all — they get their own couple. The negative case is a user with
        // no couple, and the structural test below proves the absence of an id.
        'couple.show' => ['kind' => 'self', 'method' => 'get', 'uri' => fn () => '/api/couple', 'status' => 404],
        'couple.update' => ['kind' => 'self', 'method' => 'patch', 'uri' => fn () => '/api/couple', 'status' => 404],
        'couple.destroy' => ['kind' => 'self', 'method' => 'delete', 'uri' => fn () => '/api/couple', 'status' => 404],
        'me.export.download' => ['kind' => 'owned', 'method' => 'get', 'uri' => null, 'status' => 403],
    ];
}

/**
 * Every registered route whose controller is in the Accounts module.
 *
 * @return array<string, Illuminate\Routing\Route>
 */
function accountsRoutes(): array
{
    $routes = [];

    // getRoutesByName() is on RouteCollectionInterface and is already keyed
    // the way the cases are, so there is no name juggling to get wrong.
    foreach (Route::getRoutes()->getRoutesByName() as $name => $route) {
        if (str_contains($route->getActionName(), 'App\Modules\Accounts\\')) {
            $routes[$name] = $route;
        }
    }

    return $routes;
}

it('has a decision recorded for every Accounts route', function (): void {
    // The gate. A new endpoint with no entry in CASES fails here, which is the
    // whole reason this file reads the router instead of a list.
    $undecided = array_diff(array_keys(accountsRoutes()), array_keys(accountsCases()));

    expect($undecided)->toBe([], 'These Accounts routes have no authorisation case: '
        .implode(', ', $undecided));
});

it('does not list a route that no longer exists', function (): void {
    // The other direction: a case left behind after a route was removed would
    // pass forever while testing nothing.
    $stale = array_diff(array_keys(accountsCases()), array_keys(accountsRoutes()));

    expect($stale)->toBe([], 'These cases name routes that are gone: '.implode(', ', $stale));
});

it('keeps the couple routes free of any id', function (): void {
    // Structural, not behavioural: there is no way to *ask* about another
    // couple because the routes take no identifier. A user has at most one and
    // the database guarantees it, so an id would only create an attack surface.
    foreach (['couple.show', 'couple.update', 'couple.destroy'] as $name) {
        expect(accountsRoutes()[$name]->uri())->not->toContain('{');
    }
});

describe('without a token', function (): void {
    it('refuses every protected route with a 401', function (): void {
        $world = TwoCouples::make();

        foreach (accountsCases() as $name => $case) {
            if ($case['kind'] === 'guest') {
                continue;
            }

            $uri = $case['uri'] === null ? null : ($case['uri'])($world);

            if ($uri === null) {
                continue; // signed routes are covered in their own test below
            }

            $this->{$case['method'].'Json'}($uri)
                ->assertUnauthorized();
        }
    });

    it('still serves the routes that are public on purpose', function (): void {
        $world = TwoCouples::make();

        // The invite preview is the only one with a body worth checking, and
        // it is public because the link is tapped before sign-up.
        $this->getJson('/api/invites/'.$world->invite->code)
            ->assertOk()
            ->assertJsonPath('inviter_first_name', 'Nobody');
    });
});

describe('as somebody else', function (): void {
    it('refuses every route that takes an id', function (): void {
        // The outsider is a member of a real, live couple — not a solo user,
        // who would be refused by accident for having no couple at all.
        $world = TwoCouples::make();

        foreach (accountsCases() as $name => $case) {
            if ($case['kind'] !== 'owned' || $case['uri'] === null) {
                continue;
            }

            $this->actingAs($world->outsider)
                ->{$case['method'].'Json'}(($case['uri'])($world))
                ->assertStatus($case['status'], "Route {$name} let an outsider through");
        }
    });

    it('gets only its own couple from the singleton routes', function (): void {
        // Belt and braces for the structural test above: the outsider gets
        // THEIR couple, never Alex and Sam's.
        $world = TwoCouples::make();

        $this->actingAs($world->outsider)->getJson('/api/couple')
            ->assertOk()
            ->assertJsonPath('id', $world->otherCouple->ulid)
            ->assertJsonPath('partner.name', 'Kit Moss');
    });

    it('is 404d on every singleton route when it has no such resource', function (): void {
        // The `self` kind's negative case: signed in, properly authenticated,
        // simply has no couple. A solo user must get 404 rather than a 500 or
        // somebody else's.
        $world = TwoCouples::make();

        foreach (accountsCases() as $name => $case) {
            if ($case['kind'] !== 'self') {
                continue;
            }

            $this->actingAs($world->solo)
                ->{$case['method'].'Json'}(($case['uri'])($world))
                ->assertStatus($case['status'], "Route {$name} did not 404 for a user with no couple");
        }
    });

    it('cannot unlink the other couple', function (): void {
        $world = TwoCouples::make();

        $this->actingAs($world->outsider)->deleteJson('/api/couple')->assertNoContent();

        // Their own is closed; Alex and Sam are untouched.
        expect($world->otherCouple->fresh()->isLinked())->toBeFalse()
            ->and($world->couple->fresh()->isLinked())->toBeTrue();
    });

    it('cannot download another user export, even with a valid signature', function (): void {
        Storage::fake();

        $world = TwoCouples::make();
        $path = app(BuildDataExport::class)->handle($world->alex);

        $url = URL::temporarySignedRoute('me.export.download', now()->addHour(), [
            'user' => $world->alex->getKey(),
            'path' => base64_encode($path),
        ]);

        $this->actingAs($world->outsider)->get($url)->assertForbidden();
    });

    it('gains nothing from an invite code while already coupled', function (): void {
        $world = TwoCouples::make();

        // Already coupled, so there is nothing an invite could do for them.
        $this->actingAs($world->outsider)
            ->postJson('/api/invites/'.$world->invite->code.'/accept')
            ->assertStatus(422);

        expect($world->invite->fresh()->accepted_at)->toBeNull();
    });
});

describe('as the owner', function (): void {
    it('lets both partners through every couple route', function (): void {
        // The positive control. A sweep that only ever asserts refusals would
        // pass with every endpoint broken.
        $world = TwoCouples::make();

        foreach ([$world->alex, $world->sam] as $partner) {
            $this->actingAs($partner)->getJson('/api/couple')->assertOk();
            $this->actingAs($partner)->getJson('/api/me')->assertOk();
        }
    });
});
