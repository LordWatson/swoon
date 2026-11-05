<?php

declare(strict_types=1);

/**
 * The schema check.
 *
 * It validates against the same array that went out as
 * `output_config.format`, so these tests build their schemas the way a task
 * does and check replies against them â€” not against a second description of
 * the same shape, which is the thing that drifts.
 */

use App\Modules\Ai\Support\SchemaValidator;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\ObjectSchema;

/**
 * @param  Closure(JsonSchema): array<string, Type>  $properties
 * @return array<string, mixed>
 */
function jsonSchemaFor(Closure $properties): array
{
    return (new ObjectSchema($properties(new JsonSchemaTypeFactory)))->toSchema();
}

/**
 * The shape SafetyGuard asks for, built the way the task declares it.
 *
 * @return array<string, mixed>
 */
function screenSchema(): array
{
    return jsonSchemaFor(fn (JsonSchema $schema): array => [
        'concern' => $schema->string()->enum(['none', 'crisis', 'unclear'])->required(),
        'confidence' => $schema->string()->enum(['low', 'medium', 'high'])->required(),
    ]);
}

/**
 * A nested shape, because the real tasks return lists of objects.
 *
 * @return array<string, mixed>
 */
function planSchema(): array
{
    return jsonSchemaFor(fn (JsonSchema $schema): array => [
        'options' => $schema->array()->min(2)->max(3)->items(
            $schema->object([
                'title' => $schema->string()->min(3)->max(60)->required(),
                'cost_pence' => $schema->integer()->min(0)->required(),
                'at_home' => $schema->boolean()->required(),
                'notes' => $schema->string(),
            ])
        )->required(),
    ]);
}

function schemaCheck(): SchemaValidator
{
    return new SchemaValidator;
}

describe('a reply that matches', function (): void {
    it('has no problems', function (): void {
        expect(schemaCheck()->problems(screenSchema(), ['concern' => 'none', 'confidence' => 'high']))
            ->toBe([]);
    });

    it('passes a nested shape', function (): void {
        expect(schemaCheck()->problems(planSchema(), [
            'options' => [
                ['title' => 'Bread and the moors', 'cost_pence' => 2400, 'at_home' => false],
                ['title' => 'Nothing at all', 'cost_pence' => 0, 'at_home' => true, 'notes' => 'ideal'],
            ],
        ]))->toBe([]);
    });
});

describe('a reply that does not', function (): void {
    it('names a missing field', function (): void {
        expect(schemaCheck()->problems(screenSchema(), ['concern' => 'none']))
            ->toBe(['the response is missing confidence']);
    });

    it('names a field of the wrong type', function (): void {
        expect(schemaCheck()->problems(screenSchema(), ['concern' => 1, 'confidence' => 'high']))
            ->toBe(['concern should be string, not int']);
    });

    it('rejects a field nobody asked for', function (): void {
        // Rejected rather than ignored: every schema we send sets
        // additionalProperties: false, so an extra field means this is not the
        // reply we asked for, and dropping it quietly would hide that.
        expect(schemaCheck()->problems(screenSchema(), [
            'concern' => 'none',
            'confidence' => 'high',
            'advice' => 'you should talk to someone',
        ]))->toBe(['the response has a field nobody asked for: advice']);
    });

    it('rejects a value outside the enum', function (): void {
        expect(schemaCheck()->problems(screenSchema(), ['concern' => 'worried', 'confidence' => 'high']))
            ->toBe(["concern should be one of none, crisis, unclear, not 'worried'"]);
    });

    it('reports every problem, not just the first', function (): void {
        expect(schemaCheck()->problems(screenSchema(), ['confidence' => 'certain']))
            ->toHaveCount(2);
    });

    it('says only once that a field is the wrong type', function (): void {
        // A string check and an enum check on something that is not a string
        // would be two complaints about one mistake.
        expect(schemaCheck()->problems(screenSchema(), ['concern' => [], 'confidence' => 'high']))
            ->toHaveCount(1);
    });

    it('points at the item inside a list', function (): void {
        expect(schemaCheck()->problems(planSchema(), [
            'options' => [
                ['title' => 'Bread and the moors', 'cost_pence' => 2400, 'at_home' => false],
                ['title' => 'No', 'cost_pence' => 'free', 'at_home' => true],
            ],
        ]))->toBe([
            'options[1].title should be at least 3 characters, it is 2',
            'options[1].cost_pence should be integer, not string',
        ]);
    });

    it('counts a list that is too short', function (): void {
        expect(schemaCheck()->problems(planSchema(), [
            'options' => [['title' => 'Only one', 'cost_pence' => 0, 'at_home' => true]],
        ]))->toBe(['options should have at least 2 items, it has 1']);
    });

    it('counts a list that is too long', function (): void {
        $option = ['title' => 'Repeated', 'cost_pence' => 0, 'at_home' => true];

        expect(schemaCheck()->problems(planSchema(), ['options' => array_fill(0, 4, $option)]))
            ->toBe(['options should have at most 3 items, it has 4']);
    });

    it('will not take an object where a list belongs', function (): void {
        // The message says "object" rather than PHP's "array", which would
        // otherwise read "should be array, not array".
        expect(schemaCheck()->problems(planSchema(), ['options' => ['first' => []]]))
            ->toBe(['options should be array, not object']);
    });
});

describe('types', function (): void {
    it('accepts an integer where a number belongs', function (): void {
        $schema = jsonSchemaFor(fn (JsonSchema $s): array => ['score' => $s->number()->required()]);

        expect(schemaCheck()->problems($schema, ['score' => 4]))->toBe([]);
    });

    it('accepts null only where the schema allows it', function (): void {
        $nullable = jsonSchemaFor(fn (JsonSchema $s): array => ['note' => $s->string()->nullable()->required()]);
        $strict = jsonSchemaFor(fn (JsonSchema $s): array => ['note' => $s->string()->required()]);

        expect(schemaCheck()->problems($nullable, ['note' => null]))->toBe([])
            ->and(schemaCheck()->problems($strict, ['note' => null]))->toHaveCount(1);
    });

    it('treats an empty array as an empty object', function (): void {
        // PHP cannot tell `{}` from `[]` after json_decode, and a schema with
        // no required fields can legitimately come back empty.
        $schema = jsonSchemaFor(fn (JsonSchema $s): array => [
            'extras' => $s->object(['colour' => $s->string()])->required(),
        ]);

        expect(schemaCheck()->problems($schema, ['extras' => []]))->toBe([]);
    });
});

it('refuses to quietly skip a constraint it does not understand', function (): void {
    // The alternative is a constraint declared in a task's schema that nothing
    // checks â€” a hole that would never announce itself.
    expect(fn (): array => schemaCheck()->problems(
        ['type' => 'object', 'properties' => ['email' => ['type' => 'string', 'format' => 'email']]],
        ['email' => 'not an address'],
    ))->toThrow(RuntimeException::class, 'does not implement the JSON Schema keyword [format]');
});
