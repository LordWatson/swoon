<?php

declare(strict_types=1);

/**
 * `ai:report-costs`.
 *
 * It exists so that the first time anybody asks what the AI bill is, the
 * answer is already written down rather than a query somebody has to invent
 * while worried.
 */

use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\UsageLogger;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\Authorisation\TwoCouples;

usingDatabase();

function spend(TwoCouples $world, string $task, AiOutcome $outcome, int $millipence): void
{
    $row = (new UsageLogger)->record(
        outcome: $outcome,
        module: 'DatePlanner',
        task: $task,
        coupleId: $world->couple->getKey(),
    );

    DB::table('ai_usage')->where('id', $row->getKey())->update(['cost_millipence' => $millipence]);
}

it('totals yesterday by task and outcome', function (): void {
    $log = Log::spy();

    $world = TwoCouples::make();

    spend($world, 'date_plan', AiOutcome::Ok, 3_232);
    spend($world, 'date_plan', AiOutcome::InvalidOutput, 1_000);
    spend($world, 'safety_screen', AiOutcome::Ok, 6);

    $this->artisan('ai:report-costs')->assertSuccessful();

    $log->shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
        return $message === 'AI cost report'
            && $context['total_pence'] === 5
            && count($context['by_task_and_outcome']) === 3;
    });
});

it('reports no couple, ever', function (): void {
    // A daily log line listing couples by cost would be a standing export of
    // who uses the product most, and nobody asked for one.
    $log = Log::spy();

    $world = TwoCouples::make();

    spend($world, 'date_plan', AiOutcome::Ok, 3_232);

    $this->artisan('ai:report-costs')->assertSuccessful();

    $log->shouldHaveReceived('info')->withArgs(function (string $message, array $context): bool {
        return ! str_contains((string) json_encode($context), 'couple');
    });
});

it('ignores anything older than the window', function (): void {
    $log = Log::spy();

    $world = TwoCouples::make();

    spend($world, 'date_plan', AiOutcome::Ok, 3_232);
    AiUsage::query()->update(['created_at' => Carbon::now('UTC')->subDays(5)]);

    $this->artisan('ai:report-costs')->assertSuccessful();

    $log->shouldHaveReceived('info')->withArgs(
        fn (string $message, array $context): bool => $context['total_pence'] === 0,
    );
});

it('can look further back when asked', function (): void {
    $log = Log::spy();

    $world = TwoCouples::make();

    spend($world, 'date_plan', AiOutcome::Ok, 3_232);
    AiUsage::query()->update(['created_at' => Carbon::now('UTC')->subDays(5)]);

    $this->artisan('ai:report-costs', ['--days' => 7])->assertSuccessful();

    $log->shouldHaveReceived('info')->withArgs(
        fn (string $message, array $context): bool => $context['total_pence'] === 4,
    );
});

it('is scheduled', function (): void {
    // A command nobody runs is a command nobody reads.
    $scheduled = array_map(
        fn (Event $event): string => (string) $event->command,
        app(Schedule::class)->events(),
    );

    expect(implode(' ', $scheduled))->toContain('ai:report-costs');
});
