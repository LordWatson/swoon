<?php

declare(strict_types=1);

/**
 * Which driver answers, and the proof that it is never the real one in tests.
 *
 * "No test makes a real AI call" is the kind of promise that quietly stops
 * being true, so it is asserted here from both ends: the container hands back
 * the fake and has never even built the real driver, and the real driver —
 * resolved on purpose — refuses before it can open a connection, because the
 * suite leaves the API key blank.
 */

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Drivers\AnthropicDriver;
use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Support\AiRequest;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/** Rebind after changing config, since the driver is a singleton. */
function driverWith(string $name): AiDriver
{
    config(['ai.driver' => $name]);

    app()->forgetInstance(AiDriver::class);

    return app(AiDriver::class);
}

describe('driver selection', function (): void {
    it('gives the suite the fake', function (): void {
        expect(config('ai.driver'))->toBe('fake')
            ->and(app(AiDriver::class))->toBeInstanceOf(FakeDriver::class);
    });

    it('never even constructs the real driver', function (): void {
        app(AiDriver::class);

        // Not "no call was made" but "the thing that makes calls does not
        // exist in this process".
        expect(app()->resolved(AnthropicDriver::class))->toBeFalse();
    });

    it('gives anthropic when config says so', function (): void {
        expect(driverWith('anthropic'))->toBeInstanceOf(AnthropicDriver::class);
    });

    it('refuses a name it does not know', function (): void {
        expect(fn (): AiDriver => driverWith('openai'))
            ->toThrow(InvalidArgumentException::class, 'Unknown AI driver [openai]');
    });
});

describe('the real driver in the test environment', function (): void {
    it('refuses to call anything, because the suite leaves the key blank', function (): void {
        // This is the assertion behind the acceptance criterion. Even with the
        // real driver deliberately bound and a well-formed request in hand, no
        // request can leave this process.
        expect(config('ai.providers.anthropic.key'))->toBeEmpty();

        $request = new AiRequest(
            task: 'safety_screen',
            prompt: 'anything at all',
            promptVersion: 'safety_screen/v1',
            schema: fn (JsonSchema $schema): array => ['concern' => $schema->string()->required()],
        );

        expect(fn (): mixed => driverWith('anthropic')->generate($request))
            ->toThrow(RuntimeException::class, 'ANTHROPIC_API_KEY is not set');
    });
});

describe('a request', function (): void {
    $schema = fn (JsonSchema $schema): array => ['concern' => $schema->string()->required()];

    it('takes its model, effort and ceiling from config, never a literal', function () use ($schema): void {
        $request = new AiRequest('safety_screen', 'p', 'safety_screen/v1', $schema);

        expect($request->model)->toBe(config('ai.models.light'))
            ->and($request->effort)->toBe('low')
            ->and($request->maxTokens)->toBe(512);
    });

    it('follows a model key change in config', function () use ($schema): void {
        config(['ai.models.light' => 'a-different-model-id']);

        expect((new AiRequest('safety_screen', 'p', 'safety_screen/v1', $schema))->model)
            ->toBe('a-different-model-id');
    });

    it('rejects a task that config does not declare', function () use ($schema): void {
        expect(fn (): AiRequest => new AiRequest('not_a_task', 'p', 'v1', $schema))
            ->toThrow(InvalidArgumentException::class, 'Unknown AI task [not_a_task]');
    });

    it('rejects a task pointing at a model key that does not exist', function () use ($schema): void {
        config(['ai.tasks.safety_screen.model' => 'enormous']);

        expect(fn (): AiRequest => new AiRequest('safety_screen', 'p', 'v1', $schema))
            ->toThrow(InvalidArgumentException::class, 'names model key [enormous]');
    });
});
