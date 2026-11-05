<?php

declare(strict_types=1);

namespace App\Modules\Ai\Support;

use InvalidArgumentException;
use RuntimeException;
use Stringable;

/**
 * Prompts are versioned files, and the version in use is pinned in config.
 *
 * `resources/prompts/<name>/<version>.md`, with the live version per prompt in
 * `config('ai.prompts.active')`. Dropping a `v2.md` next to `v1.md` therefore
 * changes nothing at all: promoting it is a deliberate config edit, reviewed
 * like any other. A released version is never edited in place, because
 * `ai_generations` rows point at it and history has to stay true.
 *
 * Everything here fails loudly. An unpinned prompt, a missing file, a slot
 * with no value, a value with no slot — each throws, because the alternative
 * is a prompt that goes to the model with a hole in it and comes back with a
 * confidently wrong answer nobody can explain.
 */
final class PromptRegistry
{
    /**
     * Read the pinned version of a prompt and fill in its variables.
     *
     * @param  array<string, mixed>  $variables
     */
    public function resolve(string $name, array $variables = []): RenderedPrompt
    {
        $version = $this->pinnedVersion($name);
        $reference = $name.'/'.$version;

        return new RenderedPrompt(
            name: $name,
            version: $version,
            text: $this->render($this->read($name, $version), $variables, $reference),
        );
    }

    /**
     * The version `config('ai.prompts.active')` pins this prompt at.
     */
    public function pinnedVersion(string $name): string
    {
        $this->ensureIsASafeName($name, 'prompt');

        $version = config('ai.prompts.active.'.$name);

        if (! is_string($version) || preg_match('/^v\d+$/', $version) !== 1) {
            throw new InvalidArgumentException(
                "Prompt [{$name}] has no pinned version in config('ai.prompts.active'). A prompt file is never used until it is pinned there."
            );
        }

        return $version;
    }

    /**
     * Where a prompt file lives. Public because the suite asserts that every
     * pinned prompt has a file, which is a check about paths, not content.
     */
    public function pathFor(string $name, ?string $version = null): string
    {
        $version ??= $this->pinnedVersion($name);

        $this->ensureIsASafeName($name, 'prompt');
        $this->ensureIsASafeName($version, 'version');

        // Forward slashes throughout: config('ai.prompts.path') never ends in
        // a separator, and is_file() takes either on Windows.
        return (string) config('ai.prompts.path').'/'.$name.'/'.$version.'.md';
    }

    private function read(string $name, string $version): string
    {
        $path = $this->pathFor($name, $version);

        if (! is_file($path)) {
            throw new RuntimeException(
                "Prompt [{$name}] is pinned at [{$version}] but {$path} does not exist."
            );
        }

        return (string) file_get_contents($path);
    }

    /**
     * Replace every `{{ slot }}` with its value.
     *
     * One pass over the template, so a value containing `{{ ... }}` is text
     * and not a slot — a couple writing "{{ nickname }}" in a note cannot
     * reach another variable.
     *
     * Values are otherwise untrusted: they are what two people typed. Nothing
     * here tries to sanitise them, because escaping prose for a language model
     * is theatre. The real defences are a declared output schema and
     * SafetyGuard, both after the fact and both tested.
     *
     * @param  array<string, mixed>  $variables
     */
    private function render(string $template, array $variables, string $reference): string
    {
        $filled = [];

        $rendered = preg_replace_callback(
            '/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/',
            function (array $match) use ($variables, &$filled, $reference): string {
                $slot = $match[1];

                if (! array_key_exists($slot, $variables)) {
                    throw new InvalidArgumentException(
                        "Prompt [{$reference}] has a slot [{$slot}] with no value. Sending the prompt with an empty slot would ask the model to guess."
                    );
                }

                $filled[$slot] = true;

                return $this->stringify($variables[$slot], $slot, $reference);
            },
            $template,
        );

        $unused = array_diff(array_keys($variables), array_keys($filled));

        if ($unused !== []) {
            // Not harmless: a task's allow-list (P2.T05) names exactly what it
            // may see, so a value the prompt never asks for means the
            // allow-list and the prompt have drifted apart, and the next
            // reviewer cannot tell which one is right.
            throw new InvalidArgumentException(
                "Prompt [{$reference}] was given values it has no slot for: ".implode(', ', $unused).'.'
            );
        }

        return (string) $rendered;
    }

    private function stringify(mixed $value, string $slot, string $reference): string
    {
        return match (true) {
            is_string($value) => $value,
            is_int($value), is_float($value) => (string) $value,
            // "yes"/"no" rather than PHP's "1"/"" — an empty slot is exactly
            // what this class exists to prevent, and false is a real answer.
            is_bool($value) => $value ? 'yes' : 'no',
            // Lists and nested payloads go in as JSON. No loops, filters or
            // conditionals: a prompt is a document, and a template language
            // would be one more thing to review for a leak.
            is_array($value) => (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $value instanceof Stringable => (string) $value,
            default => throw new InvalidArgumentException(
                "Prompt [{$reference}] slot [{$slot}] was given ".get_debug_type($value).', which has no sensible text form. null is rejected on purpose: decide what absence should say and pass that.'
            ),
        };
    }

    private function ensureIsASafeName(string $value, string $what): void
    {
        if (preg_match('/^[a-z0-9_]+$/', $value) !== 1) {
            throw new InvalidArgumentException("Invalid {$what} name [{$value}].");
        }
    }
}
