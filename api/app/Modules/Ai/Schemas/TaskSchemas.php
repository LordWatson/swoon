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
            'date_plan' => self::datePlan(),

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
    /**
     * Three date options, and the schema is what makes it three.
     *
     * "Exactly 3" is in the prompt as well, but a prompt is a request and a
     * schema is a rule: `minItems` and `maxItems` both at 3 mean a reply with
     * two options is `invalid_output` rather than a thin screen.
     *
     * Declared now, used from Phase 3, same as the allow-list - so the shape
     * is reviewable and testable before the prompt that asks for it exists.
     * The fields are the ones `docs/AI_PROMPTS.md` specifies.
     *
     * @return Closure(JsonSchema): array<string, Type>
     */
    private static function datePlan(): Closure
    {
        return fn (JsonSchema $schema): array => [
            'options' => $schema->array()->min(3)->max(3)->items(
                $schema->object([
                    'title' => $schema->string()->min(3)->max(80)->required(),
                    'emoji' => $schema->string()->min(1)->max(8)->required(),
                    'summary' => $schema->string()->min(10)->max(400)->required(),
                    'timeline' => $schema->array()->min(1)->items(
                        $schema->object([
                            'time' => $schema->string()->max(40)->required(),
                            'what' => $schema->string()->max(200)->required(),
                        ])
                    )->required(),
                    // Integer pence, like every other amount in the app.
                    'estimated_cost_pence' => $schema->integer()->min(0)->required(),
                    'travel_notes' => $schema->string()->max(300)->required(),
                    'why_both' => $schema->string()->max(200)
                        ->description('One line on how this bridges both answers.')
                        ->required(),
                ])
            )->required(),
        ];
    }

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
