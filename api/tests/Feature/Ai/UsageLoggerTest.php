<?php

declare(strict_types=1);

/**
 * Cost logging.
 *
 * Two things are being protected here. One is the arithmetic: three units meet
 * in `Cost` and none of them are interchangeable, so the sums are checked
 * against numbers worked out by hand. The other is ADR-027 â€” a safety trip
 * must leave a cost row and no trace of whose it was.
 */

use App\Modules\Ai\Models\AiFeedback;
use App\Modules\Ai\Models\AiGeneration;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\AiResult;
use App\Modules\Ai\Support\Cost;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Responses\Data\TextUsage;
use Tests\Authorisation\TwoCouples;

usingDatabase();

function usageLog(): UsageLogger
{
    return new UsageLogger;
}

function result(string $model, int $input, int $output): AiResult
{
    return new AiResult(
        structured: ['anything' => true],
        text: '{}',
        model: $model,
        usage: new TextUsage(inputTokens: $input, outputTokens: $output),
    );
}

describe('the arithmetic', function (): void {
    it('prices an Opus call the way a calculator does', function (): void {
        // Opus 5.5 is $4.00 / $20.00 per million tokens, held as 400 / 2000
        // US cents. At 80p to the dollar:
        //   3100 x 400  = 1,240,000
        //   1400 x 2000 = 2,800,000
        //   total 4,040,000 cent-tokens x 80 / 100,000 = 3,232 millipence
        // which is 3.232p.
        expect(Cost::millipence(config('ai.models.strong'), new TextUsage(3_100, 1_400)))
            ->toBe(3_232);
    });

    it('prices a Haiku screen, which is where rounding to pence would lose it', function (): void {
        //   620 x 10 = 6,200
        //    14 x 50 =   700
        //   6,900 x 80 / 100,000 = 5.52 -> 6 millipence, which is 0.006p.
        // Rounded to whole pence this would be zero, and every safety screen
        // the product makes would log as free (ADR-026).
        expect(Cost::millipence(config('ai.models.light'), new TextUsage(620, 14)))
            ->toBe(6);
    });

    it('follows a price change in config rather than a hardcoded rate', function (): void {
        // The reason prices live in config: a row written today stays
        // comparable with one from last year after the provider changes.
        config(['ai.usd_to_pence' => 160]);

        expect(Cost::millipence(config('ai.models.strong'), new TextUsage(3_100, 1_400)))
            ->toBe(6_464);
    });

    it('costs nothing for no tokens', function (): void {
        expect(Cost::millipence(config('ai.models.strong'), new TextUsage))->toBe(0);
    });

    it('rounds millipence up when it reaches a budget', function (): void {
        // A guard that rounds a spend down lets the last call through free,
        // every time.
        expect(Cost::toPence(1))->toBe(1)
            ->and(Cost::toPence(1_000))->toBe(1)
            ->and(Cost::toPence(1_001))->toBe(2)
            ->and(Cost::toPence(0))->toBe(0);
    });

    it('says so loudly when a model has no price', function (): void {
        $log = Log::spy();

        expect(Cost::millipence('a-model-nobody-priced', new TextUsage(1_000, 1_000)))->toBe(0);

        $log->shouldHaveReceived('warning');
    });

    it('has a price for every model the app can choose', function (): void {
        // The gate behind the warning above: changing config('ai.models') to a
        // model with no price fails here rather than under-reporting quietly
        // in production.
        $unpriced = array_values(array_filter(
            (array) config('ai.models'),
            fn (string $model): bool => ! is_array(config('ai.pricing_usd_cents_per_mtok.'.$model)),
        ));

        expect($unpriced)->toBe([]);
    });
});

describe('a row for every call', function (): void {
    it('records a successful call in full', function (): void {
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::Ok,
            module: 'DatePlanner',
            task: 'date_plan',
            promptVersion: 'date_plan/v1',
            result: result(config('ai.models.strong'), 3_100, 1_400),
            coupleId: $world->couple->id,
            userId: $world->alex->id,
            durationMs: 8_420,
        );

        $row = AiUsage::sole();

        expect($row->outcome)->toBe(AiOutcome::Ok)
            ->and($row->module)->toBe('DatePlanner')
            ->and($row->task)->toBe('date_plan')
            // Split, so "which prompt" and "which version" are both queryable.
            ->and($row->prompt_name)->toBe('date_plan')
            ->and($row->prompt_version)->toBe('v1')
            ->and($row->model)->toBe(config('ai.models.strong'))
            ->and($row->input_tokens)->toBe(3_100)
            ->and($row->output_tokens)->toBe(1_400)
            ->and($row->cost_millipence)->toBe(3_232)
            ->and($row->duration_ms)->toBe(8_420)
            ->and($row->couple_id)->toBe($world->couple->id);
    });

    it('records a reply in the wrong shape, because we paid for it', function (): void {
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::InvalidOutput,
            module: 'DatePlanner',
            task: 'date_plan',
            promptVersion: 'date_plan/v1',
            result: result(config('ai.models.strong'), 3_000, 50),
            coupleId: $world->couple->id,
        );

        expect(AiUsage::sole()->cost_millipence)->toBeGreaterThan(0);
    });

    it('records a refusal that never reached the model', function (): void {
        // "Nothing happened" and "it was refused" must be different facts in
        // the table, not both absence.
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::Capped,
            module: 'DatePlanner',
            task: 'date_plan',
            coupleId: $world->couple->id,
        );

        $row = AiUsage::sole();

        expect($row->outcome)->toBe(AiOutcome::Capped)
            ->and($row->model)->toBeNull()
            ->and($row->input_tokens)->toBe(0)
            ->and($row->cost_millipence)->toBe(0)
            ->and($row->couple_id)->toBe($world->couple->id);
    });

    it('records an error with whatever it managed to spend', function (): void {
        usageLog()->record(
            outcome: AiOutcome::Error,
            module: 'Recap',
            task: 'weekly_recap',
            result: result(config('ai.models.strong'), 1_000, 0),
        );

        expect(AiUsage::sole()->outcome)->toBe(AiOutcome::Error);
    });

    it('has a case for every way a call can end', function (): void {
        expect(AiOutcome::cases())->toHaveCount(5);
    });
});

describe('a safety trip', function (): void {
    it('leaves a cost row with no couple and no user', function (): void {
        // ADR-027. The screen was paid for, so the cost is logged. Nothing in
        // this database says who it was about.
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::SafetyTripped,
            module: 'DatePlanner',
            task: 'safety_screen',
            promptVersion: 'safety_screen/v1',
            result: result(config('ai.models.light'), 620, 14),
            coupleId: $world->couple->id,
            userId: $world->alex->id,
        );

        $row = AiUsage::sole();

        expect($row->couple_id)->toBeNull()
            ->and($row->user_id)->toBeNull()
            // But the money is still accounted for.
            ->and($row->cost_millipence)->toBe(6)
            ->and($row->outcome)->toBe(AiOutcome::SafetyTripped);
    });

    it('drops the attribution even when a caller passes it', function (): void {
        // Enforced in the logger rather than trusted to every caller, because
        // there will be more callers than there are people who read ADR-027.
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::SafetyTripped,
            module: 'LoveNotes',
            task: 'safety_screen',
            coupleId: $world->couple->id,
            userId: $world->sam->id,
        );

        expect(AiUsage::whereNotNull('couple_id')->count())->toBe(0)
            ->and(AiUsage::whereNotNull('user_id')->count())->toBe(0);
    });

    it('does not spend the couple allowance', function (): void {
        // The side effect of ADR-027, and the right one: charging somebody's
        // monthly allowance for describing a crisis would be grotesque.
        $world = TwoCouples::make();

        usageLog()->record(
            outcome: AiOutcome::SafetyTripped,
            module: 'DatePlanner',
            task: 'safety_screen',
            coupleId: $world->couple->id,
        );

        expect(AiUsage::where('couple_id', $world->couple->id)->count())->toBe(0);
    });

    it('is the only outcome that hides its couple', function (): void {
        foreach (AiOutcome::cases() as $outcome) {
            expect($outcome->mayNameTheCouple())
                ->toBe($outcome !== AiOutcome::SafetyTripped);
        }
    });
});

describe('history', function (): void {
    it('supersedes a generation rather than overwriting it', function (): void {
        $world = TwoCouples::make();

        $first = AiGeneration::create([
            'couple_id' => $world->couple->id,
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            'prompt_version' => 'v1',
            'payload_hash' => str_repeat('a', 64),
            'output' => ['options' => [['title' => 'The first idea']]],
        ]);

        $second = AiGeneration::create([
            'couple_id' => $world->couple->id,
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            // A new prompt version must not rewrite what they were shown.
            'prompt_version' => 'v2',
            'payload_hash' => str_repeat('a', 64),
            'output' => ['options' => [['title' => 'The better idea']]],
        ]);

        $first->supersede($second);

        expect($first->fresh()->isSuperseded())->toBeTrue()
            ->and($first->fresh()->supersededBy->getKey())->toBe($second->getKey())
            // The old one is still there, still saying what it said.
            ->and($first->fresh()->output)->toBe(['options' => [['title' => 'The first idea']]])
            ->and($first->fresh()->prompt_version)->toBe('v1')
            ->and(AiGeneration::count())->toBe(2);
    });

    it('gives every generation a public identifier', function (): void {
        $world = TwoCouples::make();

        $generation = AiGeneration::create([
            'couple_id' => $world->couple->id,
            'module' => 'Recap',
            'task' => 'weekly_recap',
            'prompt_name' => 'weekly_recap',
            'prompt_version' => 'v1',
            'payload_hash' => str_repeat('b', 64),
            'output' => ['summary' => 'A good week'],
        ]);

        expect($generation->ulid)->toHaveLength(26);
    });

    it('keeps a hash of the payload and never the payload', function (): void {
        $world = TwoCouples::make();

        $generation = AiGeneration::create([
            'couple_id' => $world->couple->id,
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            'prompt_version' => 'v1',
            'payload_hash' => hash('sha256', 'the filtered payload'),
            'output' => ['options' => []],
        ]);

        expect($generation->payload_hash)->toHaveLength(64)
            ->and($generation->getAttributes())->not->toHaveKey('payload');
    });

    it('allows one rating per person per generation', function (): void {
        $world = TwoCouples::make();

        $generation = AiGeneration::create([
            'couple_id' => $world->couple->id,
            'module' => 'DatePlanner',
            'task' => 'date_plan',
            'prompt_name' => 'date_plan',
            'prompt_version' => 'v1',
            'payload_hash' => str_repeat('c', 64),
            'output' => ['options' => []],
        ]);

        $generation->rateBy($world->alex, 'down', 'too far to travel');

        // A second tap from the same person must not read as two people.
        expect(fn (): AiFeedback => $generation->rateBy($world->alex, 'up'))
            ->toThrow(QueryException::class);

        expect($generation->feedback()->count())->toBe(1);
    });
});
