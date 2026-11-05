<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use RuntimeException;

/**
 * Does the model's reply actually match the shape we asked for?
 *
 * Anthropic's structured output is meant to guarantee this, and in practice it
 * does. This is the backstop anyway, because "the provider promised" is not a
 * thing to find out you were wrong about in front of a couple, and because the
 * fake driver and any future provider come through the same door.
 *
 * It checks against **the very array that was sent** as
 * `output_config.format` — `AiRequest::jsonSchema()` — rather than a second
 * description of the same shape. There is nothing to keep in step.
 *
 * It returns problems rather than throwing. Invalid output is an expected
 * outcome with its own usage row and its own single retry (see
 * `GenerateValidated`), not an exception, and the caller is the one that knows
 * what to do about it.
 *
 * **Extra fields are rejected**, not ignored: every schema we send sets
 * `additionalProperties: false`, so a field we did not ask for means this is
 * not the reply we asked for, and quietly dropping it would hide that.
 */
final class SchemaValidator
{
    /**
     * Keywords this validator understands.
     *
     * Anything else throws rather than being skipped. A constraint declared in
     * a task's schema and silently ignored here would be a hole nobody would
     * notice, so adding one has to come with the code that checks it.
     *
     * Public because the suite walks every declared task schema and fails on a
     * keyword that is not in this list - so the hole cannot wait for
     * production to show itself.
     */
    public const KNOWN = [
        'type', 'properties', 'required', 'additionalProperties', 'items',
        'enum', 'minItems', 'maxItems', 'uniqueItems',
        'minLength', 'maxLength', 'minimum', 'maximum', 'multipleOf',
        // Annotations. Nothing to check.
        'title', 'description', 'default',
    ];

    /**
     * @param  array<string, mixed>  $schema  What was sent as output_config.format.
     * @param  array<string, mixed>  $structured  What came back.
     * @return list<string> Problems in plain words. Empty means the reply is good.
     */
    public function problems(array $schema, array $structured): array
    {
        $problems = [];

        $this->check($schema, $structured, 'the response', $problems);

        return $problems;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $problems
     */
    private function check(array $schema, mixed $value, string $path, array &$problems): void
    {
        foreach (array_keys($schema) as $keyword) {
            if (! in_array($keyword, self::KNOWN, true)) {
                throw new RuntimeException(
                    'SchemaValidator does not implement the JSON Schema keyword ['.$keyword.']. Teach it before using it in a task schema.'
                );
            }
        }

        $types = $this->typesOf($schema);

        if ($types !== [] && ! $this->matchesAnyType($types, $value)) {
            // Stop here. Every further check would be a second complaint about
            // the same mistake.
            $problems[] = $path.' should be '.implode(' or ', $types).', not '.$this->shapeOf($value);

            return;
        }

        if ($value === null) {
            return;
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            $this->checkEnum($schema['enum'], $value, $path, $problems);
        }

        if (is_string($value)) {
            $this->checkString($schema, $value, $path, $problems);
        }

        if (is_int($value) || is_float($value)) {
            $this->checkNumber($schema, $value, $path, $problems);
        }

        if (is_array($value) && in_array('object', $types, true)) {
            $this->checkObject($schema, $value, $path, $problems);
        }

        if (is_array($value) && in_array('array', $types, true)) {
            $this->checkList($schema, $value, $path, $problems);
        }
    }

    /**
     * @param  array<array-key, mixed>  $allowed
     * @param  list<string>  $problems
     */
    private function checkEnum(array $allowed, mixed $value, string $path, array &$problems): void
    {
        if (in_array($value, $allowed, true)) {
            return;
        }

        $readable = array_map(
            static fn (mixed $option): string => is_scalar($option) ? (string) $option : get_debug_type($option),
            $allowed,
        );

        $problems[] = $path.' should be one of '.implode(', ', $readable).', not '.var_export($value, true);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<array-key, mixed>  $value
     * @param  list<string>  $problems
     */
    private function checkObject(array $schema, array $value, string $path, array &$problems): void
    {
        $properties = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        $required = is_array($schema['required'] ?? null) ? $schema['required'] : [];

        foreach ($required as $field) {
            if (! array_key_exists((string) $field, $value)) {
                $problems[] = $path.' is missing '.$field;
            }
        }

        if (($schema['additionalProperties'] ?? null) === false) {
            foreach (array_diff(array_keys($value), array_keys($properties)) as $extra) {
                $problems[] = $path.' has a field nobody asked for: '.$extra;
            }
        }

        foreach ($properties as $field => $fieldSchema) {
            if (array_key_exists((string) $field, $value) && is_array($fieldSchema)) {
                $this->check($fieldSchema, $value[$field], $this->join($path, (string) $field), $problems);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  array<array-key, mixed>  $value
     * @param  list<string>  $problems
     */
    private function checkList(array $schema, array $value, string $path, array &$problems): void
    {
        $count = count($value);
        $minItems = $schema['minItems'] ?? null;
        $maxItems = $schema['maxItems'] ?? null;

        if (is_int($minItems) && $count < $minItems) {
            $problems[] = $path.' should have at least '.$minItems.' items, it has '.$count;
        }

        if (is_int($maxItems) && $count > $maxItems) {
            $problems[] = $path.' should have at most '.$maxItems.' items, it has '.$count;
        }

        if (($schema['uniqueItems'] ?? null) === true && count($this->unique($value)) !== $count) {
            $problems[] = $path.' repeats an item';
        }

        $items = $schema['items'] ?? null;

        if (is_array($items)) {
            foreach ($value as $index => $item) {
                $this->check($items, $item, $path.'['.$index.']', $problems);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $problems
     */
    private function checkString(array $schema, string $value, string $path, array &$problems): void
    {
        $length = mb_strlen($value);
        $minLength = $schema['minLength'] ?? null;
        $maxLength = $schema['maxLength'] ?? null;

        if (is_int($minLength) && $length < $minLength) {
            $problems[] = $path.' should be at least '.$minLength.' characters, it is '.$length;
        }

        if (is_int($maxLength) && $length > $maxLength) {
            $problems[] = $path.' should be at most '.$maxLength.' characters, it is '.$length;
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $problems
     */
    private function checkNumber(array $schema, int|float $value, string $path, array &$problems): void
    {
        $minimum = $schema['minimum'] ?? null;
        $maximum = $schema['maximum'] ?? null;
        $multipleOf = $schema['multipleOf'] ?? null;

        if ((is_int($minimum) || is_float($minimum)) && $value < $minimum) {
            $problems[] = $path.' should be at least '.$minimum.', it is '.$value;
        }

        if ((is_int($maximum) || is_float($maximum)) && $value > $maximum) {
            $problems[] = $path.' should be at most '.$maximum.', it is '.$value;
        }

        if (is_int($multipleOf) && $multipleOf !== 0 && fmod((float) $value, (float) $multipleOf) !== 0.0) {
            $problems[] = $path.' should be a multiple of '.$multipleOf.', it is '.$value;
        }
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return list<string>
     */
    private function typesOf(array $schema): array
    {
        $type = $schema['type'] ?? null;

        if (is_string($type)) {
            return [$type];
        }

        if (! is_array($type)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $one): string => is_string($one) ? $one : get_debug_type($one),
            $type,
        ));
    }

    /**
     * @param  list<string>  $types
     */
    private function matchesAnyType(array $types, mixed $value): bool
    {
        foreach ($types as $type) {
            $matches = match ($type) {
                'string' => is_string($value),
                'integer' => is_int($value),
                // JSON has one number type, and an integer is a valid number.
                'number' => is_int($value) || is_float($value),
                'boolean' => is_bool($value),
                'null' => $value === null,
                'array' => is_array($value) && array_is_list($value),
                // JSON objects arrive as PHP associative arrays, and `[]` is
                // both an empty object and an empty list — accept it as either.
                'object' => is_array($value) && ($value === [] || ! array_is_list($value)),
                default => false,
            };

            if ($matches) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, mixed>  $value
     * @return array<array-key, mixed>
     */
    private function unique(array $value): array
    {
        // array_unique() compares as strings and chokes on nested arrays,
        // and a list item here can be an object.
        $seen = [];

        foreach ($value as $item) {
            if (! in_array($item, $seen, true)) {
                $seen[] = $item;
            }
        }

        return $seen;
    }

    /**
     * The value's shape in the schema's own words.
     *
     * `get_debug_type()` calls a list and an object both "array", which turns
     * a type mismatch into the message "should be array, not array".
     */
    private function shapeOf(mixed $value): string
    {
        if (! is_array($value)) {
            return get_debug_type($value);
        }

        return array_is_list($value) && $value !== [] ? 'array' : 'object';
    }

    private function join(string $path, string $field): string
    {
        return $path === 'the response' ? $field : $path.'.'.$field;
    }
}
