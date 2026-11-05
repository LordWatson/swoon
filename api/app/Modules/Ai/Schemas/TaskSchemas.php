<?php

declare(strict_types=1);

namespace App\Modules\Ai\Schemas;

use Closure;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use InvalidArgumentException;

/**
 * The output shape every AI task must answer in, in one readable place.
 *
 * One file rather than a class per task, because the point is that a reviewer
 * can read every shape the product asks a model for in a single sitting. Each
 * phase adds its own case next to the prompt file it writes.
 *
 * The same declaration does two jobs: it becomes the `output_config.format`
 * sent to Anthropic, and it is what `SchemaValidator` checks the reply against.
 * One source, so the thing we asked for and the thing we accept cannot drift.
 */
final class TaskSchemas
{
    /**
     * @return Closure(JsonSchema): array<string, Type>
     */
    public static function for(string $task): Closure
    {
        return match ($task) {
            'safety_screen' => self::safetyScreen(),

            default => throw new InvalidArgumentException(
                "AI task [{$task}] has no declared output schema. Add it to ".self::class.'.'
            ),
        };
    }

    /**
     * A classification and nothing else.
     *
     * SafetyGuard never writes a reply, so the screen has no field it could
     * write one in. `unclear` is a value the model can choose rather than a
     * fallback we infer, because a model that is unsure should be able to say
     * so plainly - and `unclear` counts as a concern (P2.T04).
     *
     * @return Closure(JsonSchema): array<string, Type>
     */
    private static function safetyScreen(): Closure
    {
        return fn (JsonSchema $schema): array => [
            'concern' => $schema->string()
                ->enum(['none', 'crisis', 'abuse', 'self_harm', 'harm_to_others', 'unclear'])
                ->description('The single most serious concern present, or "none".')
                ->required(),
            'confidence' => $schema->string()
                ->enum(['low', 'medium', 'high'])
                ->description('How sure you are of that classification.')
                ->required(),
        ];
    }
}
