<?php

declare(strict_types=1);

/**
 * The fake driver.
 *
 * Its recording is what later phases assert against: that the pinned prompt
 * version was the one sent, that a private field never reached the prompt,
 * that a capped request made no call at all. So the recording is tested here,
 * on its own, before anything depends on it.
 */

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Support\AiRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Exceptions\ProviderConnectionException;

function fakeDriver(): FakeDriver
{
    return app(FakeDriver::class);
}

function screenRequest(string $prompt = 'Screen this.'): AiRequest
{
    return new AiRequest(
        task: 'safety_screen',
        prompt: $prompt,
        promptVersion: 'safety_screen/v1',
        schema: fn (JsonSchema $schema): array => [
            'concern' => $schema->string()->required(),
            'confidence' => $schema->string()->required(),
        ],
    );
}

it('answers from the fixture for the task', function (): void {
    $result = fakeDriver()->generate(screenRequest());

    expect($result->structured)->toBe(['concern' => 'none', 'confidence' => 'high'])
        ->and($result->usage->inputTokens)->toBe(420)
        ->and($result->usage->outputTokens)->toBe(18);
});

it('reports the model the task asked for, so cost arithmetic is real', function (): void {
    expect(fakeDriver()->generate(screenRequest())->model)->toBe(config('ai.models.light'));
});

it('records the task, the rendered prompt and the prompt version', function (): void {
    $driver = fakeDriver();

    $driver->generate(screenRequest('Alex said something worrying.'));

    expect($driver->callCount())->toBe(1)
        ->and($driver->lastCall()->task)->toBe('safety_screen')
        ->and($driver->lastCall()->prompt)->toBe('Alex said something worrying.')
        ->and($driver->lastCall()->promptVersion)->toBe('safety_screen/v1');
});

it('is the one instance the rest of the application uses', function (): void {
    // The helper above resolves FakeDriver directly. If that were a different
    // object from the bound AiDriver, every assertion in this file would be
    // about a recording nobody wrote to, and would silently pass.
    expect(app(AiDriver::class))->toBe(fakeDriver());

    fakeDriver()->generate(screenRequest());

    expect(fakeDriver()->callCount())->toBe(1);
});

it('separates calls by task', function (): void {
    $driver = fakeDriver();

    $driver->respondWith(['structured' => ['anything' => true]]);
    $driver->generate(new AiRequest(
        task: 'spark_question',
        prompt: 'A question please.',
        promptVersion: 'spark_question/v1',
        schema: fn (JsonSchema $schema): array => ['question' => $schema->string()->required()],
    ));
    $driver->generate(screenRequest());

    expect($driver->callsFor('safety_screen'))->toHaveCount(1)
        ->and($driver->callsFor('spark_question'))->toHaveCount(1)
        ->and($driver->callsFor('date_plan'))->toBeEmpty();
});

it('says plainly when nothing was called', function (): void {
    expect(fn (): AiRequest => fakeDriver()->lastCall())
        ->toThrow(RuntimeException::class, 'No AI call was made');
});

describe('queued responses', function (): void {
    it('take priority over the fixture, in order', function (): void {
        $driver = fakeDriver()->respondWith(
            ['structured' => ['concern' => 'crisis', 'confidence' => 'high']],
            ['structured' => ['concern' => 'unclear', 'confidence' => 'low']],
        );

        expect($driver->generate(screenRequest())->structured['concern'])->toBe('crisis')
            ->and($driver->generate(screenRequest())->structured['concern'])->toBe('unclear')
            // Queue exhausted, so back to the fixture.
            ->and($driver->generate(screenRequest())->structured['concern'])->toBe('none');
    });

    it('can throw, which is how a transport failure is exercised', function (): void {
        $driver = fakeDriver()->respondWith(new ProviderConnectionException('anthropic is down'));

        expect(fn (): mixed => $driver->generate(screenRequest()))
            ->toThrow(ProviderConnectionException::class);

        // Still recorded: we paid for the attempt in time if not in tokens,
        // and the usage log has to show it (P2.T06).
        expect($driver->callCount())->toBe(1);
    });

    it('refuse a payload with no structured key', function (): void {
        $driver = fakeDriver()->respondWith(['structure' => ['oops' => true]]);

        expect(fn (): mixed => $driver->generate(screenRequest()))
            ->toThrow(RuntimeException::class, "has no 'structured' key");
    });

    it('allow empty output on purpose, for the validation tests', function (): void {
        $driver = fakeDriver()->respondWith(['structured' => []]);

        expect($driver->generate(screenRequest())->structured)->toBe([]);
    });
});

it('fails loudly when a task has no fixture', function (): void {
    expect(fn (): mixed => fakeDriver()->generate(new AiRequest(
        task: 'moment_tagging',
        prompt: 'Tag this.',
        promptVersion: 'moment_tagging/v1',
        schema: fn (JsonSchema $schema): array => ['tags' => $schema->array()->required()],
    )))->toThrow(RuntimeException::class, 'No AI fixture for task [moment_tagging]');
});
