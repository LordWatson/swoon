<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;

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
     * @param  string  $task  A key in `config('ai.tasks')`.
     * @param  string  $prompt  The fully rendered prompt. Nothing is interpolated after this point, so a privacy test can assert a value is absent by reading this one string.
     * @param  string  $promptVersion  e.g. "date_plan/v1", recorded on every `ai_usage` row.
     * @param  Closure(JsonSchema): array<string, Type>  $schema  Required, and deliberately so: a task with no schema would have nothing for SchemaValidator to check, which is the one step standing between the model and a couple.
     */
    public function __construct(
        public readonly string $task,
        public readonly string $prompt,
        public readonly string $promptVersion,
        public readonly Closure $schema,
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
    }
}
