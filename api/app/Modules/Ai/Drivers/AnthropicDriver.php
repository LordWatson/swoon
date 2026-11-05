<?php

declare(strict_types=1);

namespace App\Modules\Ai\Drivers;

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\AiResult;
use App\Modules\Ai\Support\TaskAgent;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

/**
 * The real door to Claude, over laravel/ai.
 *
 * The API key is read from `config('ai.providers.anthropic.key')`, which
 * laravel/ai merges into our own `config/ai.php` under the same key — one
 * source of truth for the secret, server side only, never shipped in the app.
 */
final readonly class AnthropicDriver implements AiDriver
{
    public function generate(AiRequest $request): AiResult
    {
        $this->ensureKeyIsConfigured();

        $response = (new TaskAgent($request))->prompt(
            $request->prompt,
            provider: Lab::Anthropic,
            model: $request->model,
        );

        // Always a StructuredAgentResponse: TaskAgent declares structured
        // output and refuses an empty schema, so laravel/ai has a format to
        // send and a structure to parse back.
        return new AiResult(
            structured: $response->structured,
            text: $response->text,
            model: $response->meta->model ?? $request->model,
            usage: $response->usage,
        );
    }

    /**
     * Refuse to try without a key.
     *
     * The test suite sets no `ANTHROPIC_API_KEY`, so this is also what makes
     * "no test reaches the model" true by construction rather than by
     * everybody remembering: if a future change ever binds this driver during
     * a test, it stops here instead of opening a connection.
     */
    private function ensureKeyIsConfigured(): void
    {
        if (blank(config('ai.providers.anthropic.key'))) {
            throw new RuntimeException(
                'ANTHROPIC_API_KEY is not set, so no AI call can be made. Set it in api/.env, or set AI_DRIVER=fake to use fixtures.'
            );
        }
    }
}
