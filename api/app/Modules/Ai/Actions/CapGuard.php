<?php

declare(strict_types=1);

namespace App\Modules\Ai\Actions;

use App\Modules\Accounts\Models\Couple;
use App\Modules\Ai\Models\AiUsage;
use App\Modules\Ai\Support\AiOutcome;
use App\Modules\Ai\Support\CapReason;
use App\Modules\Ai\Support\CapVerdict;
use App\Modules\Ai\Support\Cost;
use App\Modules\Billing\Support\Entitlement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Checked **before** a job is dispatched, so a refusal costs nothing.
 *
 * Three ceilings, in the order a couple meets them:
 *
 * 1. the **fair-use cap** for their tier, per task, per calendar month;
 * 2. a **per-couple cost guard**, far above the caps — in normal use
 *    unreachable, so hitting it means a bug or abuse rather than a keen couple;
 * 3. a **global daily cost guard**, which is our problem and not theirs.
 *
 * **Only successful generations count against the cap.** A reply in the wrong
 * shape, a transport failure, a guard that broke — none of those gave the
 * couple anything, and charging their monthly allowance for our failure would
 * be unkind. The money those attempts cost is still logged, and it is the cost
 * guard's job to notice if there is a lot of it. The feature cap counts what
 * they got; the cost guard counts what we spent.
 *
 * The month boundary is **local midnight in the couple's timezone**, so a
 * couple in Auckland is not told their allowance resets eleven hours late.
 */
final readonly class CapGuard
{
    public function check(Couple $couple, string $task): CapVerdict
    {
        $resetsAt = $this->nextReset($couple);
        $limit = $this->limitFor($couple, $task);
        $used = $this->usedThisMonth($couple, $task, $resetsAt);

        $globalCeiling = (int) config('ai.cost_guard.global_daily_pence');
        $spentToday = $this->globalSpendToday();

        // Our ceiling first: if everyone's spending has crossed the global
        // guard, nothing about this couple's allowance is relevant.
        if ($spentToday >= $globalCeiling) {
            // This is the alert. `critical` is what the error reporter picks
            // up, and it names no couple because it is not about one.
            Log::critical('The global AI cost guard tripped. All AI generation is refused until the day rolls over.', [
                'spent_today_pence' => $spentToday,
                'ceiling_pence' => $globalCeiling,
            ]);

            return CapVerdict::refused(
                CapReason::GlobalCostGuard,
                $used,
                $limit,
                $resetsAt,
                // Not "we have run out of money". True, vague, and calm.
                "Something's busy at our end 💛 Give it a little while and try again.",
            );
        }

        if ($limit === 0) {
            return CapVerdict::refused(
                CapReason::NotOnThisTier,
                $used,
                0,
                $resetsAt,
                ucfirst($this->label($task)).' come with Together 💛',
            );
        }

        if ($this->coupleSpendThisMonth($couple, $resetsAt) >= (int) config('ai.cost_guard.couple_monthly_pence')) {
            return CapVerdict::refused(
                CapReason::CoupleCostGuard,
                $used,
                $limit,
                $resetsAt,
                'That is all the AI we can do for you this month 💛 It comes back on '.$this->resetDate($resetsAt).'.',
            );
        }

        if ($used >= $limit) {
            return CapVerdict::refused(
                CapReason::MonthlyCap,
                $used,
                $limit,
                $resetsAt,
                // The copy PRICING.md § 7 asks for: no guilt, and a date.
                "That's your ".$this->label($task).' for this month 💝 They come back on '.$this->resetDate($resetsAt).'.',
            );
        }

        return CapVerdict::allowed($used, $limit, $resetsAt);
    }

    /**
     * Every capped task for this couple, for `GET /api/ai/allowance`.
     *
     * Read from the tier's own cap list rather than from `config('ai.tasks')`,
     * because an internal task like `safety_screen` has no allowance and
     * showing one would invite a question about it.
     *
     * @return array<string, CapVerdict>
     */
    public function allowance(Couple $couple): array
    {
        $allowance = [];

        foreach (array_keys($this->capsFor($couple)) as $task) {
            $allowance[(string) $task] = $this->check($couple, (string) $task);
        }

        return $allowance;
    }

    /**
     * Local midnight on the first of next month, in the couple's timezone.
     *
     * Also the start of the window: the current month runs from one of these
     * to the next, so one calculation defines both ends.
     */
    public function nextReset(Couple $couple): Carbon
    {
        return Carbon::now($couple->timezone())->startOfMonth()->addMonth();
    }

    private function usedThisMonth(Couple $couple, string $task, Carbon $resetsAt): int
    {
        return AiUsage::query()
            ->where('couple_id', $couple->getKey())
            ->where('task', $task)
            // Only what they actually got. See the class docblock.
            ->where('outcome', AiOutcome::Ok->value)
            ->where('created_at', '>=', $resetsAt->copy()->subMonth())
            ->count();
    }

    private function coupleSpendThisMonth(Couple $couple, Carbon $resetsAt): int
    {
        return Cost::toPence((int) AiUsage::query()
            ->where('couple_id', $couple->getKey())
            // Every outcome, not just the successful ones: this guard is about
            // money, and a retry loop spends money without giving anything.
            ->where('created_at', '>=', $resetsAt->copy()->subMonth())
            ->sum('cost_millipence'));
    }

    private function globalSpendToday(): int
    {
        // UTC, deliberately: "today" for a ceiling that protects the business
        // is our day, not any particular couple's.
        return Cost::toPence((int) AiUsage::query()
            ->where('created_at', '>=', Carbon::now('UTC')->startOfDay())
            ->sum('cost_millipence'));
    }

    private function limitFor(Couple $couple, string $task): int
    {
        return (int) ($this->capsFor($couple)[$task] ?? 0);
    }

    /**
     * @return array<string, int>
     */
    private function capsFor(Couple $couple): array
    {
        /** @var array<string, int> $caps */
        $caps = (array) config('ai.caps.'.Entitlement::tierFor($couple), []);

        return $caps;
    }

    /** "date plans", for the refusal. */
    private function label(string $task): string
    {
        $label = config('ai.tasks.'.$task.'.label');

        return is_string($label) ? $label : str_replace('_', ' ', $task);
    }

    /** "1 December" — British, and in the couple's own month. */
    private function resetDate(Carbon $resetsAt): string
    {
        return $resetsAt->format('j F');
    }
}
