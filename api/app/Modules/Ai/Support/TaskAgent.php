<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/**
 * A laravel/ai agent built from an `AiRequest`.
 *
 * laravel/ai expects one agent class per job, with its model and token ceiling
 * declared as PHP attributes. Swoon has one agent and many tasks, because a
 * task is a prompt file plus a row in `config('ai.tasks')` — not a class — and
 * an attribute is a compile-time constant that cannot read config. So the
 * per-task settings arrive as provider options instead.
 */
final class TaskAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function __construct(private readonly AiRequest $request) {}

    /**
     * No system prompt.
     *
     * The rendered prompt goes in the user turn instead, so a prompt file
     * stays one versioned unit that an eval can replay whole.
     *
     * shortcut: no prompt caching, so the static half of a long prompt is paid
     * for on every call. Upgrade when a prompt passes a few thousand tokens:
     * split the file at a marker and return the half above it from here, where
     * laravel/ai's CacheInstructions attribute can reach it.
     */
    public function instructions(): string
    {
        return '';
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $properties = ($this->request->schema)($schema);

        if ($properties === []) {
            // An empty schema makes laravel/ai send no output_config at all,
            // so the model answers in free text and the response arrives
            // unstructured — with nothing for SchemaValidator to check.
            throw new InvalidArgumentException(
                "The schema for AI task [{$this->request->task}] is empty. Declare at least one property."
            );
        }

        return $properties;
    }

    /**
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        // The gateway merges these over the body it has already built, and
        // merges output_config key by key — so setting effort here leaves the
        // structured-output format it put there intact.
        //
        // No `thinking` key on purpose: these models take adaptive thinking by
        // default and reject an explicit budget_tokens with a 400.
        return [
            'max_tokens' => $this->request->maxTokens,
            'output_config' => ['effort' => $this->request->effort],
        ];
    }
}
