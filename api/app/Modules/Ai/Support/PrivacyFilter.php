<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use App\Modules\Ai\Schemas\TaskAllowList;
use InvalidArgumentException;

/**
 * Keeps the allow-listed fields and drops everything else.
 *
 * Every variable that reaches a prompt comes through here, with no exceptions,
 * because an exception is where a leak would live. What survives is decided by
 * `TaskAllowList`, which names leaves and nothing else.
 *
 * The important property is what happens to something nobody thought about. A
 * column added to a model next year, a field somebody attaches to a payload in
 * a hurry, a relation that eager-loads more than expected: none of it is
 * mentioned in the allow-list, so none of it survives. The filter does not
 * need to know a field is private. It only needs to not know it at all.
 */
final class PrivacyFilter
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function apply(string $task, array $payload): array
    {
        $allowed = TaskAllowList::for($task);

        /** @var array<string, mixed> $filtered */
        $filtered = $this->keep($payload, $allowed, '');

        return $filtered;
    }

    /**
     * @param  array<array-key, mixed>  $input
     * @param  list<string>  $allowed
     * @return array<array-key, mixed>
     */
    private function keep(array $input, array $allowed, string $prefix): array
    {
        $isList = array_is_list($input);
        $kept = [];

        foreach ($input as $key => $value) {
            // A position in a list is a `*`, so an allow-list entry never has
            // to name an index and a path can be compared as a plain string.
            $segment = $isList ? '*' : (string) $key;
            $path = $prefix === '' ? $segment : $prefix.'.'.$segment;

            if (in_array($path, $allowed, true)) {
                $kept[$key] = $this->leafOnly($value, $path);

                continue;
            }

            if (! is_array($value)) {
                continue;
            }

            $child = $this->keep($value, $allowed, $path);

            // Inside a list, keep the item even when nothing in it survived.
            // Dropping it would change how many things the payload describes,
            // and a model cannot tell that two partners became one.
            if ($child !== [] || $isList) {
                $kept[$key] = $child;
            }
        }

        if (! $isList) {
            return $kept;
        }

        $kept = array_values($kept);

        // But a list where *nothing* survived is not a list of empty things,
        // it is a branch the allow-list never mentioned — a gift vault, a set
        // of check-in answers. Collapse it so the parent drops the key.
        return $this->hasContent($kept) ? $kept : [];
    }

    /**
     * @param  array<array-key, mixed>  $items
     */
    private function hasContent(array $items): bool
    {
        foreach ($items as $item) {
            if (! is_array($item) || $item !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Allow a value, as long as it cannot grow new fields.
     *
     * A list of plain values is fine - it can get longer, but a longer list of
     * dietary needs is still dietary needs. An object or a list of objects is
     * refused, because allowing one would silently allow every field added
     * inside it later, which is the one thing this class exists to stop.
     */
    private function leafOnly(mixed $value, string $path): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $item) {
            if (is_array($item) || is_object($item)) {
                throw new InvalidArgumentException(
                    "[{$path}] is allow-listed but holds an object. An allow-list entry must name a value: allowing a container would include whatever is added inside it later. Name each field instead."
                );
            }
        }

        return $value;
    }
}
