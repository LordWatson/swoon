<?php

declare(strict_types=1);

/**
 * The real driver's own mapping, without a network.
 *
 * `FakeDriver` replaces this driver in every other test, which would leave the
 * adapter itself — the part that turns a laravel/ai response into an
 * `AiResult`, and a task's config row into provider options — with no coverage
 * at all. laravel/ai's own agent fake covers it here: a focused test of our
 * adapter is exactly what that fake is good for, where ours is for the chain
 * around it (ADR-024).
 */

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Drivers\AnthropicDriver;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\TaskAgent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;

function planRequest(): AiRequest
{
    return new AiRequest(
        task: 'date_plan',
        prompt: 'Plan a Saturday in Leeds.',
        promptVersion: 'date_plan/v1',
        schema: fn (JsonSchema $schema): array => ['title' => $schema->string()->required()],
    );
}

describe('provider options', function (): void {
    it('carry the effort and ceiling the task asked for', function (): void {
        expect((new TaskAgent(planRequest()))->providerOptions(Lab::Anthropic))->toBe([
            'max_tokens' => 8000,
            'output_config' => ['effort' => 'high'],
        ]);
    });

    it('follow a config change rather than a constant', function (): void {
        config(['ai.tasks.date_plan.effort' => 'low', 'ai.tasks.date_plan.max_tokens' => 1234]);

        expect((new TaskAgent(planRequest()))->providerOptions(Lab::Anthropic))->toBe([
            'max_tokens' => 1234,
            'output_config' => ['effort' => 'low'],
        ]);
    });

    it('send no thinking budget, which these models reject outright', function (): void {
        expect((new TaskAgent(planRequest()))->providerOptions(Lab::Anthropic))
            ->not->toHaveKey('thinking');
    });
});

it('refuses a schema with no properties', function (): void {
    // Without this, laravel/ai sends no output_config, the model answers in
    // free text, and the response comes back with nothing for SchemaValidator
    // to check — a silent hole in the one rule that protects the couple.
    $request = new AiRequest(
        task: 'date_plan',
        prompt: 'p',
        promptVersion: 'date_plan/v1',
        schema: fn (JsonSchema $schema): array => [],
    );

    expect(fn (): array => (new TaskAgent($request))->schema(new JsonSchemaTypeFactory))
        ->toThrow(InvalidArgumentException::class, 'is empty')
        ->and(fn (): array => $request->jsonSchema())
        ->toThrow(InvalidArgumentException::class, 'is empty');
});

it('maps a response onto an AiResult', function (): void {
    // A key, because this driver refuses without one. laravel/ai's fake
    // gateway is what guarantees nothing leaves the process.
    config(['ai.providers.anthropic.key' => 'not-a-real-key']);

    TaskAgent::fake([
        new StructuredTextResponse(
            ['title' => 'Bread, then the moors'],
            '{"title":"Bread, then the moors"}',
            new TextUsage(inputTokens: 3_100, outputTokens: 1_400),
            new Meta('anthropic', config('ai.models.strong')),
        ),
    ]);

    $result = (new AnthropicDriver)->generate(planRequest());

    expect($result->structured)->toBe(['title' => 'Bread, then the moors'])
        ->and($result->text)->toBe('{"title":"Bread, then the moors"}')
        ->and($result->model)->toBe(config('ai.models.strong'))
        ->and($result->usage->inputTokens)->toBe(3_100)
        ->and($result->usage->outputTokens)->toBe(1_400);

    TaskAgent::assertPromptedTimes(1);
});

it('sends the rendered prompt as the user message', function (): void {
    config(['ai.providers.anthropic.key' => 'not-a-real-key']);

    TaskAgent::fake([['title' => 'anything']]);

    (new AnthropicDriver)->generate(planRequest());

    TaskAgent::assertPrompted('Plan a Saturday in Leeds.');
});

it('is not the driver this suite runs on', function (): void {
    expect(app(AiDriver::class))->not->toBeInstanceOf(AnthropicDriver::class);
});
