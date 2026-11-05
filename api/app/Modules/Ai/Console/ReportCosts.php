<?php

declare(strict_types=1);

namespace App\Modules\Ai\Console;

use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\Support\Cost;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * What yesterday cost, by task and by outcome.
 *
 * Scheduled daily. It exists so the first time anybody asks "what is the AI
 * bill" the answer is already written down, rather than being a query somebody
 * has to invent while worried.
 *
 * It reports **no couple**. Per-couple spend is a question the cap query
 * answers when it needs to; a daily log line listing couples by cost would be
 * a standing export of who uses the product most, and nobody asked for one.
 */
final class ReportCosts extends Command
{
    protected $signature = 'ai:report-costs {--days=1 : How many days back to report}';

    protected $description = 'Log what AI generation cost, by task and outcome';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $since = Carbon::now('UTC')->startOfDay()->subDays($days);

        // `toBase()`: this is an aggregate, so the rows are sums and counts
        // rather than usage records, and hydrating models would invite reading
        // `$row->outcome` as if it were one.
        $rows = AiUsage::query()
            ->toBase()
            ->where('created_at', '>=', $since)
            ->groupBy('task', 'outcome')
            ->select([
                'task',
                'outcome',
                DB::raw('count(*) as calls'),
                DB::raw('sum(cost_millipence) as millipence'),
                DB::raw('sum(input_tokens) as input_tokens'),
                DB::raw('sum(output_tokens) as output_tokens'),
            ])
            ->orderByDesc('millipence')
            ->get();

        $totalMillipence = (int) $rows->sum('millipence');

        $breakdown = $rows->map(fn (object $row): array => [
            'task' => (string) $row->task,
            'outcome' => (string) $row->outcome,
            'calls' => (int) $row->calls,
            'pence' => Cost::toPence((int) $row->millipence),
            'input_tokens' => (int) $row->input_tokens,
            'output_tokens' => (int) $row->output_tokens,
        ])->all();

        Log::info('AI cost report', [
            'since' => $since->toIso8601String(),
            'days' => $days,
            'total_pence' => Cost::toPence($totalMillipence),
            'ceiling_pence' => (int) config('ai.cost_guard.global_daily_pence') * $days,
            'by_task_and_outcome' => $breakdown,
        ]);

        $this->table(
            ['Task', 'Outcome', 'Calls', 'Pence'],
            array_map(
                fn (array $row): array => [$row['task'], $row['outcome'], $row['calls'], $row['pence']],
                $breakdown,
            ),
        );

        $this->line('Total: '.Cost::toPence($totalMillipence).'p over '.$days.' day(s)');

        return self::SUCCESS;
    }
}
