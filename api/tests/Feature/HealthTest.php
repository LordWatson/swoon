<?php

declare(strict_types=1);

/*
 * The health endpoint is how the mobile app confirms it is pointed at a
 * reachable API, so it must stay unauthenticated, cheap, and free of database
 * access — a DB outage must not make the app look unreachable.
 */

it('reports healthy without authentication', function (): void {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJson([
            'status' => 'ok',
            'app' => config('swoon.name'),
            'environment' => 'testing',
        ])
        ->assertJsonStructure(['status', 'app', 'environment', 'version', 'time']);
});

it('names the product from config so a bad environment is obvious', function (): void {
    config()->set('swoon.name', 'Renamed');

    $this->getJson('/api/health')->assertJsonPath('app', 'Renamed');
});

it('does not leak configuration', function (): void {
    $response = $this->getJson('/api/health')->assertOk();

    $body = $response->getContent();

    expect($body)
        ->not->toContain('ANTHROPIC')
        ->not->toContain('base64:')
        ->not->toContain('password');
});
