<?php

declare(strict_types=1);

namespace App\Modules\Ai\Console;

use App\Modules\Ai\Actions\GenerateValidated;
use App\Modules\Ai\Support\AiRequest;
use App\Modules\Ai\Support\Cost;
use App\Modules\Ai\Support\PrivacyFilter;
use App\Modules\Ai\Support\PromptRegistry;
use Illuminate\Console\Command;
use JsonException;
use Throwable;

/**
 * Measure a prompt against its battery.
 *
 * Two modes, and the difference is the point.
 *
 * **Without `--live`** it costs nothing and reaches nothing. It loads the
 * battery, checks every case is well formed, and renders each one through the
 * filter and the registry. That is what CI runs: it proves the eval *can*
 * run and that every case's input still fits the prompt, which is the thing
 * that silently rots when a prompt gains a slot. It does **not** tell you
 * whether the prompt is any good; nothing free can.
 *
 * **With `--live`** it calls the real model once per case and prints what it
 * spent. Deliberate by construction: the flag, a confirmation, a real driver,
 * a real key, and never from a test.
 */
final class RunEval extends Command
{
    protected $signature = 'swoon:ai-eval
        {prompt : The prompt name, e.g. safety_screen}
        {--live : Call the real model. This spends real money.}
        {--limit=0 : Stop after this many cases, for a cheap sample}';

    protected $description = 'Run a prompt against its eval battery (dry by default)';

    public function handle(PromptRegistry $prompts, PrivacyFilter $privacy, GenerateValidated $generate): int
    {
        $prompt = (string) $this->argument('prompt');
        $live = (bool) $this->option('live');

        try {
            $cases = $this->load($prompt);
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $cases = array_slice($cases, 0, $limit);
        }

        if ($live && ! $this->maySpend(count($cases))) {
            return self::FAILURE;
        }

        $results = [];
        $millipence = 0;

        foreach ($cases as $case) {
            $request = $this->requestFor($prompt, $case, $prompts, $privacy);

            if (! $live) {
                // Rendering is the whole dry run: a case whose variables no
                // longer fit the prompt throws here, which is the failure
                // worth catching for free.
                $results[] = ['id' => $case['id'], 'verdict' => 'rendered', 'got' => '-'];

                continue;
            }

            $generation = $generate->handle($request);

            foreach ($generation->attempts as $attempt) {
                $millipence += Cost::millipence($attempt->model, $attempt->usage);
            }

            $results[] = $this->score($case, $generation->structured, $generation->problems);
        }

        $this->report($prompt, $results, $live, $millipence);

        $failed = array_filter($results, fn (array $row): bool => in_array($row['verdict'], ['wrong', 'malformed'], true));

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Every case across every battery file for this prompt.
     *
     * @return list<array<string, mixed>>
     */
    private function load(string $prompt): array
    {
        $directory = base_path('tests/Fixtures/Ai/evals/'.$prompt);

        if (! is_dir($directory)) {
            throw new JsonException("No eval battery for [{$prompt}]. Expected {$directory}.");
        }

        $cases = [];
        $seen = [];

        foreach (glob($directory.'/*.json') ?: [] as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);

            if (! is_array($decoded) || ! is_array($decoded['cases'] ?? null)) {
                throw new JsonException('Battery ['.basename($file).'] has no cases.');
            }

            $field = (string) ($decoded['expect_field'] ?? '');

            foreach ($decoded['cases'] as $case) {
                if (! is_array($case) || ! is_string($case['id'] ?? null)) {
                    throw new JsonException('A case in ['.basename($file).'] has no id.');
                }

                if (isset($seen[$case['id']])) {
                    // Two cases with one id means one of them is invisible in
                    // every report.
                    throw new JsonException('Duplicate case id ['.$case['id'].'].');
                }

                $seen[$case['id']] = true;
                $case['expect_field'] = $field;
                $cases[] = $case;
            }
        }

        if ($cases === []) {
            throw new JsonException("The battery for [{$prompt}] is empty.");
        }

        return $cases;
    }

    /**
     * @param  array<string, mixed>  $case
     */
    private function requestFor(string $prompt, array $case, PromptRegistry $prompts, PrivacyFilter $privacy): AiRequest
    {
        /** @var array<string, mixed> $variables */
        $variables = is_array($case['variables'] ?? null)
            ? $case['variables']
            : ['text' => (string) ($case['text'] ?? '')];

        // Through the filter, like every other prompt. An eval that saw more
        // than the real thing would be measuring something else.
        $rendered = $prompts->resolve($prompt, $privacy->apply($prompt, $variables));

        return new AiRequest(
            task: $prompt,
            prompt: $rendered->text,
            promptVersion: $rendered->reference(),
        );
    }

    /**
     * @param  array<string, mixed>  $case
     * @param  array<string, mixed>|null  $structured
     * @param  list<string>  $problems
     * @return array{id: string, verdict: string, got: string}
     */
    private function score(array $case, ?array $structured, array $problems): array
    {
        if ($structured === null) {
            return ['id' => (string) $case['id'], 'verdict' => 'malformed', 'got' => implode('; ', $problems)];
        }

        $field = (string) $case['expect_field'];
        $got = $field === '' ? '' : (string) ($structured[$field] ?? '');
        $expected = (string) ($case['expect'] ?? '');

        if ($field === '' || $expected === '') {
            // No declared expectation: the shape passing is the whole test.
            return ['id' => (string) $case['id'], 'verdict' => 'shape ok', 'got' => $got];
        }

        /** @var list<string> $accept */
        $accept = is_array($case['accept'] ?? null) ? $case['accept'] : [];

        return [
            'id' => (string) $case['id'],
            'verdict' => match (true) {
                $got === $expected => 'exact',
                in_array($got, $accept, true) => 'acceptable',
                default => 'wrong',
            },
            'got' => $got,
        ];
    }

    /**
     * The two gates between this command and a bill.
     */
    private function maySpend(int $cases): bool
    {
        if ($this->getLaravel()->runningUnitTests()) {
            // Not a judgement call. A test that spends money is a failure
            // whatever it was trying to prove.
            $this->components->error('A live eval cannot run from a test.');

            return false;
        }

        if (config('ai.driver') !== 'anthropic') {
            $this->components->error('AI_DRIVER is ['.(string) config('ai.driver').']. A live eval needs the real driver.');

            return false;
        }

        if (blank(config('ai.providers.anthropic.key'))) {
            $this->components->error('ANTHROPIC_API_KEY is not set, so there is nothing to call.');

            return false;
        }

        // Interactive on purpose. With --no-interaction this defaults to no,
        // so a live run cannot happen inside a script by accident.
        return $this->confirm(
            'This will make '.$cases.' real calls to Anthropic and spend real money. Go ahead?',
            false,
        );
    }

    /**
     * @param  list<array{id: string, verdict: string, got: string}>  $results
     */
    private function report(string $prompt, array $results, bool $live, int $millipence): void
    {
        $this->newLine();
        $this->components->info(($live ? 'Live eval' : 'Dry run').': '.$prompt.' ('.count($results).' cases)');

        $tally = array_count_values(array_column($results, 'verdict'));

        foreach ($tally as $verdict => $count) {
            $this->line('  '.str_pad((string) $verdict, 12).$count);
        }

        $wrong = array_values(array_filter(
            $results,
            fn (array $row): bool => in_array($row['verdict'], ['wrong', 'malformed'], true),
        ));

        if ($wrong !== []) {
            $this->newLine();
            $this->components->warn('These cases disagreed with the battery:');
            $this->table(['Case', 'Verdict', 'Got'], array_map(array_values(...), $wrong));
        }

        $this->newLine();

        if ($live) {
            // The acceptance criterion: a live run says what it spent.
            $this->line('Spent: '.$millipence.' millipence ('.Cost::toPence($millipence).'p)');
        } else {
            $this->line('Nothing was called and nothing was spent. Use --live to measure the prompt itself.');
        }
    }
}
