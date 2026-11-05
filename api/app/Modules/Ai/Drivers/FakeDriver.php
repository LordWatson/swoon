<?php

declare(strict_types=1);

namespace App\Modules\Ai\Drivers;

use App\Modules\Ai\Contracts\AiDriver;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\AiResult;
use Laravel\Ai\Responses\Data\TextUsage;
use RuntimeException;
use Throwable;

/**
 * The driver the whole test suite runs on, and the one local development uses
 * without an API key.
 *
 * It answers from `tests/Fixtures/Ai/<task>.json` by default, so a test that
 * cares about the chain rather than the wording gets a realistic response for
 * free. `respondWith()` overrides that per call for the tests that do care â€”
 * malformed output, a safety concern, a transport failure.
 *
 * Every call is recorded. That recording is how a privacy test proves a field
 * never reached a prompt and how a prompt-registry test proves the pinned
 * version was the one used: the assertion reads the request that was actually
 * sent, not the code that built it.
 *
 * Registered as a singleton (see AppServiceProvider), so the instance a test
 * asserts on is the instance the job used.
 */
final class FakeDriver implements AiDriver
{
    /** @var list<AiRequest> */
    private array $calls = [];

    /** @var list<array<string, mixed>|Throwable> */
    private array $queue = [];

    /** @var array<string, list<array<string, mixed>|Throwable>> */
    private array $perTask = [];

    public function generate(AiRequest $request): AiResult
    {
        $this->calls[] = $request;

        $next = array_key_exists($request->task, $this->perTask) && $this->perTask[$request->task] !== []
            ? array_shift($this->perTask[$request->task])
            : array_shift($this->queue);

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $this->hydrate($next ?? $this->fixtureFor($request->task), $request);
    }

    /**
     * Queue the next response(s), each a fixture-shaped array or a throwable.
     *
     * Arrays rather than `AiResult` objects on purpose: they read the same as
     * the fixture files, and the model id is filled in from the request, so a
     * test never has to name a model â€” which is what keeps model ids inside
     * config/ai.php.
     *
     * @param  array<string, mixed>|Throwable  ...$responses
     */
    public function respondWith(array|Throwable ...$responses): self
    {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }

        return $this;
    }

    /**
     * Queue the next response(s) **for one task only**.
     *
     * The whole-chain tests need this. One generation makes three calls - a
     * screen, the model, another screen - and a single queue would hand the
     * date plan to the first safety screen that asked.
     *
     * @param  array<string, mixed>|Throwable  ...$responses
     */
    public function respondTo(string $task, array|Throwable ...$responses): self
    {
        foreach ($responses as $response) {
            $this->perTask[$task][] = $response;
        }

        return $this;
    }

    /** @return list<AiRequest> */
    public function calls(): array
    {
        return $this->calls;
    }

    /** @return list<AiRequest> */
    public function callsFor(string $task): array
    {
        return array_values(array_filter($this->calls, fn (AiRequest $call): bool => $call->task === $task));
    }

    public function callCount(): int
    {
        return count($this->calls);
    }

    public function lastCall(): AiRequest
    {
        $last = end($this->calls);

        if (! $last instanceof AiRequest) {
            throw new RuntimeException('No AI call was made, so there is nothing to assert about.');
        }

        return $last;
    }

    /**
     * Read `tests/Fixtures/Ai/<task>.json`.
     *
     * A missing file is a loud failure rather than an empty response: a fake
     * that quietly returns nothing would let a test pass while asserting
     * nothing, which is worse than no fake at all.
     *
     * @return array<string, mixed>
     */
    private function fixtureFor(string $task): array
    {
        $path = base_path('tests/Fixtures/Ai/'.$task.'.json');

        if (! is_file($path)) {
            throw new RuntimeException(
                "No AI fixture for task [{$task}]. Add {$path}, or queue a response with FakeDriver::respondWith()."
            );
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            throw new RuntimeException("AI fixture [{$path}] is not valid JSON.");
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hydrate(array $payload, AiRequest $request): AiResult
    {
        if (! array_key_exists('structured', $payload)) {
            throw new RuntimeException(
                "AI fixture for task [{$request->task}] has no 'structured' key. Use \"structured\": {} to fake output that fails validation."
            );
        }

        $structured = $payload['structured'];
        $usage = is_array($payload['usage'] ?? null) ? $payload['usage'] : [];

        return new AiResult(
            structured: is_array($structured) ? $structured : [],
            text: (string) ($payload['text'] ?? ''),
            model: $request->model,
            usage: new TextUsage(
                inputTokens: (int) ($usage['input_tokens'] ?? 0),
                outputTokens: (int) ($usage['output_tokens'] ?? 0),
            ),
        );
    }
}
