<?php

declare(strict_types=1);

/**
 * Caps and the cost guards.
 *
 * The boundary is the whole test: n-1 allowed, n refused, and the same again
 * either side of a month that ends at local midnight in the couple's own
 * timezone rather than in UTC.
 */

use App\Modules\Accounts\Models\Couple;
use App\Modules\Ai\Actions\CapGuard;
use App\Modules\Ai\Drivers\FakeDriver;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\CapReason;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\Authorisation\TwoCouples;

usingDatabase();

function caps(): CapGuard
{
    return app(CapGuard::class);
}

/** `n` successful date plans this month, which is what counts against a cap. */
function usedPlans(Couple $couple, int $times, string $task = 'date_plan'): void
{
    for ($i = 0; $i < $times; $i++) {
        (new UsageLogger)->record(
            outcome: AiOutcome::Ok,
            module: 'DatePlanner',
            task: $task,
            promptVersion: $task.'/v1',
            coupleId: $couple->getKey(),
        );
    }
}

describe('the boundary', function (): void {
    it('allows the last one, refuses the one after', function (): void {
        // Spark includes one date plan a month, so n is 1 and the three cases
        // are 0, 1 and 2.
        $world = TwoCouples::make();

        expect(caps()->check($world->couple, 'date_plan')->allowed)->toBeTrue();

        usedPlans($world->couple, 1);
        expect(caps()->check($world->couple, 'date_plan')->allowed)->toBeFalse();

        usedPlans($world->couple, 1);
        expect(caps()->check($world->couple, 'date_plan')->allowed)->toBeFalse();
    });

    it('counts up to the limit on a tier with room', function (): void {
        config(['ai.caps.spark.date_plan' => 3]);

        $world = TwoCouples::make();

        usedPlans($world->couple, 2);

        $verdict = caps()->check($world->couple, 'date_plan');

        expect($verdict->allowed)->toBeTrue()
            ->and($verdict->used)->toBe(2)
            ->and($verdict->limit)->toBe(3)
            ->and($verdict->remaining())->toBe(1);

        usedPlans($world->couple, 1);

        expect(caps()->check($world->couple, 'date_plan')->remaining())->toBe(0);
    });

    it('counts the other couple separately', function (): void {
        $world = TwoCouples::make();

        usedPlans($world->couple, 1);

        expect(caps()->check($world->couple, 'date_plan')->allowed)->toBeFalse()
            ->and(caps()->check($world->otherCouple, 'date_plan')->allowed)->toBeTrue();
    });

    it('counts each task separately', function (): void {
        config(['ai.caps.spark.insights' => 2]);

        $world = TwoCouples::make();

        usedPlans($world->couple, 1);

        expect(caps()->check($world->couple, 'date_plan')->allowed)->toBeFalse()
            ->and(caps()->check($world->couple, 'insights')->allowed)->toBeTrue();
    });
});

describe('what counts', function (): void {
    it('counts only what the couple actually got', function (): void {
        // A reply in the wrong shape, a transport failure, a guard that broke
        // - none of them gave the couple anything, and charging their
        // allowance for our failure would be unkind.
        $world = TwoCouples::make();

        foreach ([AiOutcome::InvalidOutput, AiOutcome::Error, AiOutcome::Capped] as $outcome) {
            (new UsageLogger)->record(
                outcome: $outcome,
                module: 'DatePlanner',
                task: 'date_plan',
                coupleId: $world->couple->getKey(),
            );
        }

        $verdict = caps()->check($world->couple, 'date_plan');

        expect($verdict->used)->toBe(0)
            ->and($verdict->allowed)->toBeTrue();
    });

    it('does not count last month', function (): void {
        $world = TwoCouples::make();

        usedPlans($world->couple, 1);

        // Backdate the row past the start of this month.
        DB::table('ai_usage')->update(['created_at' => Carbon::now()->subMonths(2)]);

        expect(caps()->check($world->couple, 'date_plan')->used)->toBe(0);
    });
});

describe('the month boundary', function (): void {
    it('rolls over at local midnight in the couple\'s timezone, not UTC', function (): void {
        // 30 November, 23:30 in Auckland is still 10:30 UTC on the 30th -
        // same month either way. The interesting instant is the other one.
        $world = TwoCouples::make();
        $world->couple->activeMembers()->each(
            fn ($member) => $member->forceFill(['timezone' => 'Pacific/Auckland'])->save()
        );

        // 1 December, 00:30 in Auckland is 30 November, 11:30 UTC. In UTC the
        // month has not turned; for this couple it has, and their allowance is
        // back.
        Carbon::setTestNow(Carbon::parse('2026-11-30 11:30:00', 'UTC'));

        usedPlans($world->couple, 1);
        // The usage row belongs to the Auckland month that has just started,
        // so push it into the one before.
        DB::table('ai_usage')->update(['created_at' => Carbon::parse('2026-11-20 00:00:00', 'UTC')]);

        $verdict = caps()->check($world->couple->fresh(), 'date_plan');

        expect($world->couple->fresh()->timezone())->toBe('Pacific/Auckland')
            ->and($verdict->used)->toBe(0)
            ->and($verdict->allowed)->toBeTrue()
            // The next reset is 1 January in Auckland, not in London.
            ->and($verdict->resetsAt->timezone->getName())->toBe('Pacific/Auckland')
            ->and($verdict->resetsAt->format('Y-m-d'))->toBe('2027-01-01');

        Carbon::setTestNow();
    });

    it('takes the timezone of whoever joined first, so it cannot wobble', function (): void {
        // Stable rather than fair: if it depended on who asked, two partners
        // in different zones would see different allowances for a few hours
        // either side of the first.
        $world = TwoCouples::make();

        $world->alex->forceFill(['timezone' => 'Europe/London'])->save();
        $world->sam->forceFill(['timezone' => 'America/New_York'])->save();

        expect($world->couple->fresh()->timezone())->toBe('Europe/London');
    });

    it('names the reset date in the message', function (): void {
        Carbon::setTestNow(Carbon::parse('2026-11-14 12:00:00', 'Europe/London'));

        $world = TwoCouples::make();
        usedPlans($world->couple, 1);

        expect(caps()->check($world->couple, 'date_plan')->message)->toContain('1 December');

        Carbon::setTestNow();
    });
});

describe('the refusal', function (): void {
    it('is kind, and says when it comes back', function (): void {
        Carbon::setTestNow(Carbon::parse('2026-11-14 12:00:00', 'Europe/London'));

        $world = TwoCouples::make();
        usedPlans($world->couple, 1);

        $verdict = caps()->check($world->couple, 'date_plan');

        expect($verdict->reason)->toBe(CapReason::MonthlyCap)
            ->and($verdict->message)->toBe("That's your date plans for this month 💝 They come back on 1 December.");

        Carbon::setTestNow();
    });

    it('never scolds, whatever the reason', function (): void {
        // UX rule 8. These are the words that creep in when somebody writes a
        // limit message without thinking about who is reading it.
        $world = TwoCouples::make();

        config(['ai.cost_guard.global_daily_pence' => 0]);
        $messages = [caps()->check($world->couple, 'date_plan')->message];

        config(['ai.cost_guard.global_daily_pence' => 50_000, 'ai.caps.spark.date_plan' => 0]);
        $messages[] = caps()->check($world->couple, 'date_plan')->message;

        config(['ai.caps.spark.date_plan' => 1]);
        usedPlans($world->couple, 1);
        $messages[] = caps()->check($world->couple, 'date_plan')->message;

        foreach ($messages as $message) {
            expect(strtolower((string) $message))
                ->not->toContain('exceeded')
                ->not->toContain('limit reached')
                ->not->toContain('too many')
                ->not->toContain('denied')
                ->not->toContain('not allowed')
                ->not->toContain('error')
                ->not->toContain('quota');
        }
    });

    it('says a feature is not on this tier rather than pretending it reset', function (): void {
        // Spark includes no weekly recaps at all. "They come back on the 1st"
        // would be a lie.
        $world = TwoCouples::make();

        $verdict = caps()->check($world->couple, 'weekly_recap');

        expect($verdict->reason)->toBe(CapReason::NotOnThisTier)
            ->and($verdict->limit)->toBe(0)
            ->and($verdict->message)->toBe('Weekly recaps come with Together 💛');
    });

    it('refuses a task with no cap entry at all', function (): void {
        // An unknown task has no allowance rather than an unlimited one.
        $world = TwoCouples::make();

        expect(caps()->check($world->couple, 'year_in_moments')->allowed)->toBeFalse();
    });
});

describe('the cost guards', function (): void {
    it('refuses a couple that has spent beyond the runaway ceiling', function (): void {
        $world = TwoCouples::make();

        config(['ai.cost_guard.couple_monthly_pence' => 1]);

        // One row with real money on it. 500,000 millipence is £5.
        (new UsageLogger)->record(
            outcome: AiOutcome::Ok,
            module: 'DatePlanner',
            task: 'insights',
            coupleId: $world->couple->getKey(),
        );
        DB::table('ai_usage')->update(['cost_millipence' => 500_000]);

        $verdict = caps()->check($world->couple, 'date_plan');

        expect($verdict->allowed)->toBeFalse()
            ->and($verdict->reason)->toBe(CapReason::CoupleCostGuard);
    });

    it('counts every outcome towards the money, not just the successes', function (): void {
        // The division of labour: the feature cap counts what they got, the
        // cost guard counts what we spent. A retry loop spends without giving.
        $world = TwoCouples::make();

        config(['ai.cost_guard.couple_monthly_pence' => 1]);

        (new UsageLogger)->record(
            outcome: AiOutcome::InvalidOutput,
            module: 'DatePlanner',
            task: 'date_plan',
            coupleId: $world->couple->getKey(),
        );
        DB::table('ai_usage')->update(['cost_millipence' => 500_000]);

        expect(caps()->check($world->couple, 'date_plan')->reason)->toBe(CapReason::CoupleCostGuard);
    });

    it('refuses everybody and alerts when the global ceiling goes', function (): void {
        $log = Log::spy();

        $world = TwoCouples::make();

        config(['ai.cost_guard.global_daily_pence' => 1]);

        (new UsageLogger)->record(
            outcome: AiOutcome::Ok,
            module: 'Recap',
            task: 'weekly_recap',
            coupleId: $world->otherCouple->getKey(),
        );
        DB::table('ai_usage')->update(['cost_millipence' => 500_000]);

        $verdict = caps()->check($world->couple, 'date_plan');

        expect($verdict->allowed)->toBeFalse()
            ->and($verdict->reason)->toBe(CapReason::GlobalCostGuard)
            // Our problem, told calmly, and it does not mention money.
            ->and($verdict->message)->toBe("Something's busy at our end 💛 Give it a little while and try again.");

        // The alert. `critical` is what the error reporter picks up.
        $log->shouldHaveReceived('critical');
    });

    it('takes the global guard before anything about the couple', function (): void {
        // When everyone's spending has crossed the ceiling, this couple's
        // allowance is not the relevant fact.
        $world = TwoCouples::make();

        config(['ai.cost_guard.global_daily_pence' => 1, 'ai.caps.spark.date_plan' => 0]);

        (new UsageLogger)->record(
            outcome: AiOutcome::Ok,
            module: 'Recap',
            task: 'weekly_recap',
            coupleId: $world->otherCouple->getKey(),
        );
        DB::table('ai_usage')->update(['cost_millipence' => 500_000]);

        expect(caps()->check($world->couple, 'date_plan')->reason)->toBe(CapReason::GlobalCostGuard);
    });

    it('ignores yesterday when looking at today', function (): void {
        $world = TwoCouples::make();

        config(['ai.cost_guard.global_daily_pence' => 1]);

        (new UsageLogger)->record(
            outcome: AiOutcome::Ok,
            module: 'Recap',
            task: 'weekly_recap',
            coupleId: $world->otherCouple->getKey(),
        );
        DB::table('ai_usage')->update([
            'cost_millipence' => 500_000,
            'created_at' => Carbon::now('UTC')->subDay()->subHour(),
        ]);

        expect(caps()->check($world->couple, 'date_plan')->reason)->not->toBe(CapReason::GlobalCostGuard);
    });
});

it('spends nothing when it refuses', function (): void {
    // The point of checking before dispatch. The guard reads rows; it never
    // reaches the driver, so there is nothing to bill.
    $world = TwoCouples::make();

    usedPlans($world->couple, 1);

    $before = DB::table('ai_usage')->count();

    caps()->check($world->couple, 'date_plan');

    expect(DB::table('ai_usage')->count())->toBe($before)
        ->and(app(FakeDriver::class)->callCount())->toBe(0);
});
