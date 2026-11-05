<?php

declare(strict_types=1);

/**
 * The whole chain, end to end, on the fake driver.
 *
 * Every terminal outcome has a test here, and each one checks three things:
 * what the couple ends up with, what `ai_usage` says, and which event fired.
 * The second of those is the one that rots quietly if nobody asserts it — an
 * unlogged call is money we cannot account for.
 *
 * The prompt path points at `tests/Fixtures/Ai/prompts`, which holds a
 * stand-in for each prompt the chain needs. These tests are about the chain,
 * not the wording; `SafetyGuardTest` exercises the real safety prompt.
 */

use App\Modules\Ai\Actions\AiService;
use App\Modules\Ai\Actions\CapGuard;
use App\Modules\Ai\Actions\GenerateValidated;
use App\Modules\Ai\Actions\SafetyGuard;
use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Events\AiGenerationCompleted;
use App\Modules\Ai\Events\AiGenerationFailed;
use App\Modules\Ai\Events\AiSafetyTripped;
use App\Modules\Ai\Jobs\RunAiTask;
use App\Modules\Ai\Models\AiGeneration;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\CapVerdict;
use App\Modules\Ai\Support\PromptRegistry;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Symfony\Component\Finder\Finder;
use Tests\Authorisation\TwoCouples;

usingDatabase();

beforeEach(function (): void {
    config([
        'ai.prompts.path' => base_path('tests/Fixtures/Ai/prompts'),
        'ai.prompts.active' => ['safety_screen' => 'v1', 'date_plan' => 'v1'],
        // Room to ask more than once, for the regenerate tests.
        'ai.caps.spark.date_plan' => 5,
    ]);
});

function ai(): AiService
{
    return app(AiService::class);
}

function driver(): FakeDriver
{
    return app(FakeDriver::class);
}

/**
 * A date-plan payload shaped like the allow-list, with one piece of free text
 * attributed to whoever wrote it.
 *
 * @return array{array<string, mixed>, array<int, string>}
 */
function planRequestFor(TwoCouples $world): array
{
    return [
        [
            'partners' => [
                ['first_name' => 'Alex', 'answers' => ['vibe' => 'cosy', 'notes' => 'nothing too loud']],
                ['first_name' => 'Sam', 'answers' => ['vibe' => 'lively', 'notes' => '']],
            ],
            'recent_dates' => [['title' => 'That terrible film', 'vibe' => 'lively']],
        ],
        [(int) $world->alex->getKey() => 'nothing too loud please'],
    ];
}

function plan(TwoCouples $world, ?AiGeneration $supersedes = null): CapVerdict
{
    [$payload, $screen] = planRequestFor($world);

    return ai()->run(
        module: 'DatePlanner',
        task: 'date_plan',
        payload: $payload,
        couple: $world->couple,
        user: $world->alex,
        screen: $screen,
        supersedes: $supersedes,
    );
}

describe('ok', function (): void {
    it('shows the couple a plan, logs every call and says so', function (): void {
        Event::fake([AiGenerationCompleted::class, AiGenerationFailed::class, AiSafetyTripped::class]);

        $world = TwoCouples::make();

        $verdict = plan($world);

        expect($verdict->allowed)->toBeTrue();

        $generation = AiGeneration::sole();

        expect($generation->task)->toBe('date_plan')
            ->and($generation->prompt_version)->toBe('v1')
            ->and($generation->output['options'])->toHaveCount(3)
            ->and($generation->couple_id)->toBe($world->couple->id)
            ->and($generation->isSuperseded())->toBeFalse();

        // Three calls: the input screen, the model, the draft screen.
        expect(driver()->callCount())->toBe(3)
            ->and(AiUsage::count())->toBe(3)
            ->and(AiUsage::where('task', 'date_plan')->sole()->outcome)->toBe(AiOutcome::Ok)
            ->and(AiUsage::where('task', 'safety_screen')->count())->toBe(2);

        Event::assertDispatched(AiGenerationCompleted::class);
        Event::assertNotDispatched(AiGenerationFailed::class);
        Event::assertNotDispatched(AiSafetyTripped::class);
    });

    it('screens the input before it asks the expensive model', function (): void {
        // If somebody has just described a crisis, asking Opus for three date
        // ideas is the wrong thing to do with that sentence.
        $world = TwoCouples::make();

        plan($world);

        expect(driver()->calls()[0]->task)->toBe('safety_screen')
            ->and(driver()->calls()[1]->task)->toBe('date_plan')
            ->and(driver()->calls()[2]->task)->toBe('safety_screen');
    });

    it('keeps the filtered payload, so the question can be asked again', function (): void {
        $world = TwoCouples::make();

        plan($world);

        expect(AiGeneration::sole()->payload)->toHaveKey('partners');
    });

    it('sends nothing to the model that the allow-list did not pass', function (): void {
        $world = TwoCouples::make();

        [$payload, $screen] = planRequestFor($world);
        $payload['gift_vault'] = [['item' => 'a necklace']];
        $payload['partners'][0]['email'] = 'alex@example.com';

        ai()->run(
            module: 'DatePlanner',
            task: 'date_plan',
            payload: $payload,
            couple: $world->couple,
            user: $world->alex,
            screen: $screen,
        );

        $prompt = driver()->callsFor('date_plan')[0]->prompt;

        expect($prompt)->not->toContain('necklace')
            ->and($prompt)->not->toContain('alex@example.com')
            // And not into the stored payload either.
            ->and(json_encode(AiGeneration::sole()->payload))->not->toContain('necklace');
    });
});

describe('invalid_output', function (): void {
    it('shows nothing, logs both attempts, and says it failed', function (): void {
        Event::fake([AiGenerationCompleted::class, AiGenerationFailed::class]);

        $world = TwoCouples::make();

        // Two options instead of three: the schema says exactly three.
        driver()->respondTo('date_plan', ['structured' => ['options' => []]], ['structured' => ['options' => []]]);

        plan($world);

        expect(AiGeneration::count())->toBe(0)
            ->and(AiUsage::where('task', 'date_plan')->count())->toBe(2)
            ->and(AiUsage::where('task', 'date_plan')->pluck('outcome')->all())
            ->toBe([AiOutcome::InvalidOutput, AiOutcome::InvalidOutput]);

        Event::assertDispatched(AiGenerationFailed::class, fn (AiGenerationFailed $e): bool => $e->outcome === AiOutcome::InvalidOutput);
        Event::assertNotDispatched(AiGenerationCompleted::class);
    });

    it('bills us for both, because we paid for both', function (): void {
        $world = TwoCouples::make();

        driver()->respondTo(
            'date_plan',
            ['structured' => ['options' => []], 'usage' => ['input_tokens' => 3_000, 'output_tokens' => 40]],
            ['structured' => ['options' => []], 'usage' => ['input_tokens' => 3_000, 'output_tokens' => 40]],
        );

        plan($world);

        expect((int) AiUsage::where('task', 'date_plan')->sum('cost_millipence'))->toBeGreaterThan(0);
    });

    it('does not spend the couple allowance on our failure', function (): void {
        $world = TwoCouples::make();

        driver()->respondTo('date_plan', ['structured' => ['options' => []]], ['structured' => ['options' => []]]);

        plan($world);

        expect(app(CapGuard::class)->check($world->couple, 'date_plan')->used)->toBe(0);
    });
});

describe('safety_tripped', function (): void {
    it('abandons the generation before the model is asked, and signposts the writer', function (): void {
        Event::fake([AiSafetyTripped::class, AiGenerationCompleted::class, AiGenerationFailed::class]);

        $world = TwoCouples::make();

        driver()->respondTo('safety_screen', ['structured' => ['concern' => 'abuse', 'confidence' => 'high']]);

        plan($world);

        // The expensive model was never asked.
        expect(driver()->callsFor('date_plan'))->toBe([])
            ->and(AiGeneration::count())->toBe(0);

        $row = AiUsage::where('task', 'date_plan')->sole();

        expect($row->outcome)->toBe(AiOutcome::SafetyTripped)
            // ADR-027: nothing in the database says whose it was.
            ->and($row->couple_id)->toBeNull()
            ->and($row->user_id)->toBeNull();

        // The signpost goes to the person who wrote the words, and not to
        // their partner (ADR-025).
        Event::assertDispatched(
            AiSafetyTripped::class,
            fn (AiSafetyTripped $e): bool => $e->signpostUserId === (int) $world->alex->getKey(),
        );
        // Not a failure. A listener treating it as one would say "that didn't
        // work, try again", which is the wrong response entirely.
        Event::assertNotDispatched(AiGenerationFailed::class);
        Event::assertNotDispatched(AiGenerationCompleted::class);
    });

    it('discards a draft that trips the screen, after paying for it', function (): void {
        Event::fake([AiSafetyTripped::class, AiGenerationCompleted::class]);

        $world = TwoCouples::make();

        // The input is fine; the draft is not.
        driver()->respondTo(
            'safety_screen',
            ['structured' => ['concern' => 'none', 'confidence' => 'high']],
            ['structured' => ['concern' => 'crisis', 'confidence' => 'high']],
        );

        plan($world);

        expect(AiGeneration::count())->toBe(0)
            ->and(AiUsage::where('task', 'date_plan')->sole()->outcome)->toBe(AiOutcome::SafetyTripped)
            // We were still billed for the plan nobody will ever see.
            ->and(AiUsage::where('task', 'date_plan')->sole()->input_tokens)->toBe(3_100);

        Event::assertDispatched(AiSafetyTripped::class);
        Event::assertNotDispatched(AiGenerationCompleted::class);
    });

    it('leaves nothing anywhere that says what tripped it', function (): void {
        $world = TwoCouples::make();

        driver()->respondTo('safety_screen', ['structured' => ['concern' => 'self_harm', 'confidence' => 'high']]);

        plan($world);

        // Not in the usage table, and not on a screen row either: an
        // attributed screen row beside an unattributed trip would hand the
        // correlation straight back.
        expect(AiUsage::whereNotNull('couple_id')->count())->toBe(0)
            ->and(json_encode(AiUsage::all()->toArray()))->not->toContain('self_harm')
            ->and(json_encode(AiUsage::all()->toArray()))->not->toContain('nothing too loud');
    });

    it('is never retried', function (): void {
        // A clean return, not an exception, so the queue has nothing to retry
        // and the couple is not screened three times over.
        $world = TwoCouples::make();

        driver()->respondTo('safety_screen', ['structured' => ['concern' => 'abuse', 'confidence' => 'high']]);

        plan($world);

        expect(driver()->callsFor('safety_screen'))->toHaveCount(1);
    });
});

describe('capped', function (): void {
    it('makes no model call at all', function (): void {
        Event::fake([AiGenerationCompleted::class]);

        $world = TwoCouples::make();

        config(['ai.caps.spark.date_plan' => 0]);

        $verdict = plan($world);

        expect($verdict->allowed)->toBeFalse()
            ->and($verdict->message)->not->toBeNull()
            ->and(driver()->callCount())->toBe(0)
            ->and(AiGeneration::count())->toBe(0)
            ->and(AiUsage::sole()->outcome)->toBe(AiOutcome::Capped)
            ->and(AiUsage::sole()->model)->toBeNull();

        Event::assertNotDispatched(AiGenerationCompleted::class);
    });

    it('queues nothing', function (): void {
        Queue::fake();

        $world = TwoCouples::make();

        config(['ai.caps.spark.date_plan' => 0]);

        plan($world);

        Queue::assertNothingPushed();
    });
});

describe('error', function (): void {
    it('records an unpinned prompt as an error and says so', function (): void {
        Event::fake([AiGenerationFailed::class]);

        $world = TwoCouples::make();

        config(['ai.prompts.active' => ['safety_screen' => 'v1']]);

        plan($world);

        expect(AiGeneration::count())->toBe(0)
            ->and(AiUsage::where('task', 'date_plan')->sole()->outcome)->toBe(AiOutcome::Error);

        Event::assertDispatched(AiGenerationFailed::class, fn (AiGenerationFailed $e): bool => $e->outcome === AiOutcome::Error);
    });

    it('abandons everything when the screen itself breaks', function (): void {
        Event::fake([AiGenerationFailed::class, AiGenerationCompleted::class]);

        $world = TwoCouples::make();

        driver()->respondTo('safety_screen', new ProviderConnectionException('anthropic is down'));

        plan($world);

        // Fail-closed: no plan, and no signpost either, because we do not know
        // whether that text was safe.
        expect(AiGeneration::count())->toBe(0)
            ->and(driver()->callsFor('date_plan'))->toBe([])
            ->and(AiUsage::where('task', 'date_plan')->sole()->outcome)->toBe(AiOutcome::Error);

        Event::assertDispatched(AiGenerationFailed::class);
        Event::assertNotDispatched(AiGenerationCompleted::class);
    });
});

it('leaves a usage row on every single path', function (): void {
    // The one assertion that covers "no code path reaches the model without
    // logging": each arrangement below is a different exit from the job.
    $arrangements = [
        'ok' => fn () => null,
        'invalid' => fn () => driver()->respondTo('date_plan', ['structured' => []], ['structured' => []]),
        'tripped' => fn () => driver()->respondTo('safety_screen', ['structured' => ['concern' => 'abuse', 'confidence' => 'high']]),
        'screen broke' => fn () => driver()->respondTo('safety_screen', new ProviderConnectionException('down')),
        'no prompt' => fn () => config(['ai.prompts.active' => ['safety_screen' => 'v1']]),
        'capped' => fn () => config(['ai.caps.spark.date_plan' => 0]),
    ];

    foreach ($arrangements as $name => $arrange) {
        AiUsage::query()->delete();
        AiGeneration::query()->delete();

        config([
            'ai.prompts.active' => ['safety_screen' => 'v1', 'date_plan' => 'v1'],
            'ai.caps.spark.date_plan' => 5,
        ]);

        $world = TwoCouples::make();

        $arrange();

        plan($world);

        expect(AiUsage::count())->toBeGreaterThan(0, "The [{$name}] path logged nothing");
    }
});

describe('no AI call in an HTTP request', function (): void {
    it('is queued, never run inline', function (): void {
        expect(new RunAiTask('DatePlanner', 'date_plan', [], 1))->toBeInstanceOf(ShouldQueue::class);
    });

    it('answers a regenerate with 202 and a job, having called nothing', function (): void {
        Queue::fake();

        $world = TwoCouples::make();

        $generation = AiGeneration::create([
            'couple_id' => $world->couple->getKey(),
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            'prompt_version' => 'v1',
            'payload' => ['partners' => [['first_name' => 'Alex']]],
            'output' => ['options' => []],
        ]);

        $this->actingAs($world->alex)
            ->postJson('/api/ai/generations/'.$generation->ulid.'/regenerate')
            ->assertStatus(202)
            ->assertJsonPath('queued', true);

        Queue::assertPushed(RunAiTask::class);

        // The assertion that matters: the request itself touched no model.
        expect(driver()->callCount())->toBe(0);
    });
});

describe('regenerate', function (): void {
    it('supersedes the old generation rather than overwriting it', function (): void {
        $world = TwoCouples::make();

        plan($world);

        $first = AiGeneration::sole();

        plan($world, $first);

        expect(AiGeneration::count())->toBe(2)
            ->and($first->fresh()->isSuperseded())->toBeTrue()
            // The old row still says what it said.
            ->and($first->fresh()->output['options'])->toHaveCount(3);
    });

    it('counts against the cap like any other generation', function (): void {
        $world = TwoCouples::make();

        config(['ai.caps.spark.date_plan' => 2]);

        plan($world);
        $first = AiGeneration::sole();

        plan($world, $first);

        $verdict = app(CapGuard::class)->check($world->couple->fresh(), 'date_plan');

        expect($verdict->used)->toBe(2)
            ->and($verdict->allowed)->toBeFalse();
    });

    it('refuses with the cap copy rather than an error', function (): void {
        $world = TwoCouples::make();

        $generation = AiGeneration::create([
            'couple_id' => $world->couple->getKey(),
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            'prompt_version' => 'v1',
            'payload' => ['partners' => [['first_name' => 'Alex']]],
            'output' => ['options' => []],
        ]);

        config(['ai.caps.spark.date_plan' => 0]);

        $this->actingAs($world->sam)
            ->postJson('/api/ai/generations/'.$generation->ulid.'/regenerate')
            ->assertOk()
            ->assertJsonPath('queued', false)
            ->assertJsonPath('message', 'Date plans come with Together 💛');
    });
});

describe('feedback', function (): void {
    it('records a rating and says nothing back', function (): void {
        $world = TwoCouples::make();

        plan($world);
        $generation = AiGeneration::sole();

        $this->actingAs($world->alex)
            ->postJson('/api/ai/generations/'.$generation->ulid.'/feedback', [
                'rating' => 'down',
                'note' => 'too far to travel',
            ])
            ->assertNoContent();

        expect($generation->feedback()->count())->toBe(1)
            ->and($generation->feedback()->sole()->user_id)->toBe($world->alex->id);
    });

    it('lets somebody change their mind without reading as two people', function (): void {
        $world = TwoCouples::make();

        plan($world);
        $generation = AiGeneration::sole();

        foreach (['down', 'up'] as $rating) {
            $this->actingAs($world->alex)
                ->postJson('/api/ai/generations/'.$generation->ulid.'/feedback', ['rating' => $rating])
                ->assertNoContent();
        }

        expect($generation->feedback()->count())->toBe(1)
            ->and($generation->feedback()->sole()->rating)->toBe('up');
    });

    it('keeps the two partners\' ratings apart', function (): void {
        $world = TwoCouples::make();

        plan($world);
        $generation = AiGeneration::sole();

        $this->actingAs($world->alex)
            ->postJson('/api/ai/generations/'.$generation->ulid.'/feedback', ['rating' => 'down']);
        $this->actingAs($world->sam)
            ->postJson('/api/ai/generations/'.$generation->ulid.'/feedback', ['rating' => 'up']);

        expect($generation->feedback()->count())->toBe(2);
    });

    it('refuses a rating that is not a rating', function (): void {
        $world = TwoCouples::make();

        plan($world);

        $this->actingAs($world->alex)
            ->postJson('/api/ai/generations/'.AiGeneration::sole()->ulid.'/feedback', ['rating' => 'sideways'])
            ->assertStatus(422);
    });
});

it('is the only way to the model', function (): void {
    // The exit criterion for the phase, as a grep: nothing outside the AI
    // module may resolve a driver or build a request.
    $offenders = [];

    $files = Finder::create()
        ->files()
        ->name('*.php')
        ->in([app_path(), database_path()])
        ->notPath('Modules/Ai')
        // The container binding has to live somewhere, and a service provider
        // is where. It names the drivers and calls neither.
        ->notName('AppServiceProvider.php');

    foreach ($files as $file) {
        $source = (string) $file->getContents();

        foreach (['AiDriver', 'AnthropicDriver', 'GenerateValidated', 'AiRequest'] as $forbidden) {
            if (str_contains($source, $forbidden)) {
                $offenders[] = $file->getRelativePathname().' mentions '.$forbidden;
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('does not log usage for a couple that has gone', function (): void {
    // Deleted or unlinked while the job sat in the queue. Nothing to show
    // anybody, and nothing was spent.
    $world = TwoCouples::make();

    (new RunAiTask(
        module: 'DatePlanner',
        task: 'date_plan',
        payload: ['partners' => []],
        coupleId: 999_999,
    ))->handle(
        app(PromptRegistry::class),
        app(GenerateValidated::class),
        app(SafetyGuard::class),
        new UsageLogger,
    );

    expect(AiUsage::count())->toBe(0)
        ->and(driver()->callCount())->toBe(0);
});
