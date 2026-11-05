<?php

declare(strict_types=1);

/**
 * Generate, check the shape, retry once.
 *
 * The number that matters is **two**: one try and exactly one retry. Every
 * attempt is billed, so a third would cost money to learn what the second
 * already said, and the usage log has to show both.
 */

use App\Modules\Ai\Actions\GenerateValidated;
use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\Generation;
use Laravel\Ai\Exceptions\ProviderConnectionException;

function generate(): GenerateValidated
{
    return app(GenerateValidated::class);
}

function screen(): AiRequest
{
    // No schema argument: the task's declaration in TaskSchemas is the point.
    return new AiRequest('safety_screen', 'Screen this.', 'safety_screen/v1');
}

/** @return array<string, mixed> */
function good(): array
{
    return ['structured' => ['concern' => 'none', 'confidence' => 'high'], 'usage' => ['input_tokens' => 400, 'output_tokens' => 12]];
}

/** @return array<string, mixed> */
function malformed(): array
{
    return ['structured' => ['concern' => 'none'], 'usage' => ['input_tokens' => 400, 'output_tokens' => 9]];
}

it('returns the output when the shape is right, after one call', function (): void {
    $generation = generate()->handle(screen());

    expect($generation->isValid())->toBeTrue()
        ->and($generation->structured)->toBe(['concern' => 'none', 'confidence' => 'high'])
        ->and($generation->problems)->toBe([])
        ->and($generation->attempts)->toHaveCount(1)
        ->and(app(FakeDriver::class)->callCount())->toBe(1);
});

it('retries once and keeps the good answer', function (): void {
    app(FakeDriver::class)->respondWith(malformed(), good());

    $generation = generate()->handle(screen());

    expect($generation->isValid())->toBeTrue()
        ->and($generation->structured)->toBe(['concern' => 'none', 'confidence' => 'high'])
        // Both attempts, because we paid for both.
        ->and($generation->attempts)->toHaveCount(2)
        ->and($generation->attempts[0]->usage->outputTokens)->toBe(9)
        ->and($generation->attempts[1]->usage->outputTokens)->toBe(12);
});

it('gives up after exactly one retry', function (): void {
    app(FakeDriver::class)->respondWith(malformed(), malformed(), good());

    $generation = generate()->handle(screen());

    expect($generation->isValid())->toBeFalse()
        ->and($generation->structured)->toBeNull()
        ->and($generation->problems)->toBe(['the response is missing confidence'])
        ->and($generation->attempts)->toHaveCount(2)
        // The third queued response was never asked for.
        ->and(app(FakeDriver::class)->callCount())->toBe(2);
});

it('offers no way to reach malformed output', function (): void {
    app(FakeDriver::class)->respondWith(malformed(), malformed());

    // Not a rule somebody remembers: the type says the output might not be
    // there, and when the shape was wrong it is not.
    expect(generate()->handle(screen())->structured)->toBeNull();
});

it('keeps every attempt even when all of them failed', function (): void {
    app(FakeDriver::class)->respondWith(malformed(), malformed());

    $generation = generate()->handle(screen());

    expect($generation->attempts)->toHaveCount(2)
        ->and($generation->last()->usage->inputTokens)->toBe(400);
});

it('lets a transport failure through untouched', function (): void {
    // A connection failure is the job's to retry, on a different timescale and
    // for a different reason. Swallowing it here would burn the invalid-output
    // retry on a problem that has nothing to do with the shape.
    app(FakeDriver::class)->respondWith(new ProviderConnectionException('anthropic is down'));

    expect(fn (): Generation => generate()->handle(screen()))
        ->toThrow(ProviderConnectionException::class);

    expect(app(FakeDriver::class)->callCount())->toBe(1);
});

it('sends the task its declared schema', function (): void {
    generate()->handle(screen());

    $schema = app(FakeDriver::class)->lastCall()->jsonSchema();

    expect($schema['required'])->toBe(['concern', 'confidence'])
        ->and($schema['additionalProperties'])->toBeFalse()
        ->and($schema['properties']['concern']['enum'])
        ->toBe(['none', 'crisis', 'abuse', 'self_harm', 'harm_to_others', 'unclear']);
});
