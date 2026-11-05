<?php

declare(strict_types=1);

/**
 * The declared output shapes.
 *
 * Two gates here rather than two conventions: a task whose prompt is live must
 * have a declared shape, and every declared shape must only use constraints
 * `SchemaValidator` actually checks. Both would otherwise be the sort of gap
 * that waits for a couple to find it.
 */

use App\Modules\Ai\Schemas\TaskSchemas;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\SchemaValidator;

describe('safety_screen', function (): void {
    it('asks for a classification and nothing else', function (): void {
        // No field it could write a reply in. SafetyGuard never writes a
        // reply, and a schema is the only thing that can guarantee that.
        $schema = (new AiRequest('safety_screen', 'p', 'safety_screen/v1'))->jsonSchema();

        expect(array_keys($schema['properties']))->toBe(['concern', 'confidence'])
            ->and($schema['required'])->toBe(['concern', 'confidence'])
            ->and($schema['additionalProperties'])->toBeFalse();
    });

    it('offers "unclear" as a choice the model can make', function (): void {
        // Fail-closed treats `unclear` as a concern (P2.T04), but the model
        // has to be able to say it rather than have it inferred from a
        // hesitant answer in another field.
        $schema = (new AiRequest('safety_screen', 'p', 'safety_screen/v1'))->jsonSchema();

        expect($schema['properties']['concern']['enum'])->toContain('unclear')
            ->and($schema['properties']['confidence']['enum'])->toBe(['low', 'medium', 'high']);
    });
});

it('refuses a task with no declared shape', function (): void {
    expect(fn (): Closure => TaskSchemas::for('date_plan'))
        ->toThrow(InvalidArgumentException::class, 'has no declared output schema');
});

it('has a declared shape for every task whose prompt is live', function (): void {
    // A pinned prompt is a feature a couple can reach. Reaching the model
    // without a declared shape would mean reaching it without validation.
    $undeclared = [];

    foreach (array_keys((array) config('ai.prompts.active')) as $task) {
        try {
            TaskSchemas::for((string) $task);
        } catch (InvalidArgumentException) {
            $undeclared[] = (string) $task;
        }
    }

    expect($undeclared)->toBe([]);
});

it('uses only constraints the validator checks', function (): void {
    // A keyword SchemaValidator does not implement is a constraint we tell the
    // model about and then never verify.
    $unsupported = [];

    // Walks schema nodes, not every array: the keys under `properties` are
    // field names, and a field called "format" is not the keyword "format".
    $walk = function (array $node) use (&$walk, &$unsupported): void {
        foreach (array_keys($node) as $keyword) {
            if (! in_array((string) $keyword, SchemaValidator::KNOWN, true)) {
                $unsupported[] = (string) $keyword;
            }
        }

        foreach ((array) ($node['properties'] ?? []) as $child) {
            if (is_array($child)) {
                $walk($child);
            }
        }

        if (is_array($node['items'] ?? null)) {
            $walk($node['items']);
        }
    };

    foreach (declaredTasks() as $task) {
        $walk((new AiRequest($task, 'p', $task.'/v1'))->jsonSchema());
    }

    expect(array_values(array_unique($unsupported)))->toBe([]);
});

/**
 * Every task `TaskSchemas` declares.
 *
 * Read off the source rather than listed here, so a task added to the
 * declaration is covered by the gates above without anyone remembering to add
 * it in two places.
 *
 * @return list<string>
 */
function declaredTasks(): array
{
    $source = (string) file_get_contents(
        base_path('app/Modules/Ai/Schemas/TaskSchemas.php')
    );

    preg_match_all("/'([a-z0-9_]+)' => self::/", $source, $matches);

    return $matches[1];
}

it('finds the declarations it means to check', function (): void {
    // If the pattern above stopped matching, the gates would pass by checking
    // nothing at all.
    expect(declaredTasks())->toContain('safety_screen');
});
