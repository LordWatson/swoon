<?php

declare(strict_types=1);

/**
 * Model ids live in config/ai.php and nowhere else.
 *
 * A gate rather than a convention, in the same spirit as the authorisation
 * sweep and the mobile screen inventory: the rule is only real if something
 * fails when it is broken. A model id pasted into a job or a prompt is how a
 * model upgrade turns from a config change into a grep.
 */

use Symfony\Component\Finder\Finder;

it('keeps every model id inside config/ai.php', function (): void {
    $allowed = realpath(config_path('ai.php'));

    $offenders = [];

    $files = Finder::create()
        ->files()
        ->name('*.php')
        ->in([app_path(), config_path(), database_path(), resource_path(), base_path('routes'), base_path('tests')]);

    foreach ($files as $file) {
        if ($file->getRealPath() === $allowed) {
            continue;
        }

        // Any provider's id, not just Anthropic's: a fallback model pasted in
        // during an incident is exactly the sort of thing that stays.
        if (preg_match_all('/\b(claude|gpt|gemini|llama)-[a-z0-9.\-]+/i', (string) $file->getContents(), $matches) > 0) {
            $offenders[$file->getRelativePathname()] = array_values(array_unique($matches[0]));
        }
    }

    expect($offenders)->toBe([]);
});
