<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use App\Modules\Ai\Schemas\TaskSchemas;
use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;
use Laravel\Ai\ObjectSchema;

/**
 * One rendered prompt, ready to send.
 *
 * It resolves its own model, effort and token ceiling from
 * `config('ai.tasks.<task>')` in the constructor, which is the reason no model
 * id appears anywhere else in the codebase: a driver asks the request, the
 * request asks config, and a model upgrade stays a one-line config change.
 *
 * Resolving eagerly also means an unknown task or an unknown model key fails
 * at the point the request is built — before a cap is spent or a job queued —
 * rather than inside the driver with a null model id.
 */
final class AiRequest
{
    /** The model id that will answer, e.g. the value of config('ai.models.strong'). */
    public readonly string $model;

    /** "high" | "medium" | "low" — rides to Anthropic as output_config.effort. */
    public readonly string $effort;

    public readonly int $maxTokens;

    /**
     * The task's output shape, as a closure over Laravel's JSON Schema builder.
     *
     * @var Closure(JsonSchema): array<string, Type>
     */
    public readonly Closure $schema;

    /**
     * @param  string  $task  A key in `config('ai.tasks')`.
     * @param  string  $prompt  The fully rendered prompt. Nothing is interpolated after this point, so a privacy test can assert a value is absent by reading this one string.
     * @param  string  $promptVersion  e.g. "date_plan/v1", recorded on every `ai_usage` row.
     * @param  (Closure(JsonSchema): array<string, Type>)|null  $schema  Defaults to the task's declaration in `TaskSchemas`, which is where it belongs; passing one is for tests. A task has no choice about having a schema: without one there is nothing for `SchemaValidator` to check, and that is the one step standing between the model and a couple.
     */
    public function __construct(
        public readonly string $task,
        public readonly string $prompt,
        public readonly string $promptVersion,
        ?Closure $schema = null,
    ) {
        $settings = config('ai.tasks.'.$task);

        if (! is_array($settings)) {
            throw new InvalidArgumentException(
                "Unknown AI task [{$task}]. Every task needs an entry in config/ai.php under 'tasks'."
            );
        }

        $modelKey = (string) ($settings['model'] ?? '');
        $model = config('ai.models.'.$modelKey);

        if (! is_string($model) || $model === '') {
            throw new InvalidArgumentException(
                "AI task [{$task}] names model key [{$modelKey}], which is not in config('ai.models')."
            );
        }

        $this->model = $model;
        $this->effort = (string) ($settings['effort'] ?? 'medium');
        $this->maxTokens = (int) ($settings['max_tokens'] ?? 1024);
        $this->schema = $schema ?? TaskSchemas::for($task);
    }

    /**
     * The declared properties, as Laravel JSON Schema types.
     *
     * @return array<string, Type>
     */
    public function schemaProperties(): array
    {
        $properties = ($this->schema)(new JsonSchemaTypeFactory);

        if ($properties === []) {
            // An empty schema makes laravel/ai send no output_config at all,
            // so the model answers in free text and the reply comes back
            // unstructured — with nothing for SchemaValidator to check.
            throw new InvalidArgumentException(
                "The schema for AI task [{$this->task}] is empty. Declare at least one property."
            );
        }

        return $properties;
    }

    /**
     * The JSON Schema that goes out as `output_config.format`.
     *
     * `SchemaValidator` checks the reply against this same array, so the shape
     * we asked for and the shape we accept are one thing, not two.
     *
     * @return array<string, mixed>
     */
    public function jsonSchema(): array
    {
        return (new ObjectSchema($this->schemaProperties()))->toSchema();
    }
}
