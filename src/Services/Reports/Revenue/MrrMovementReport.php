<?php

namespace Coderstm\Services\Reports\Revenue;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class MrrMovementReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'starting_mrr' => ['label' => 'Starting MRR', 'type' => 'currency'], 'new_mrr' => ['label' => 'New MRR', 'type' => 'currency'], 'expansion_mrr' => ['label' => 'Expansion MRR', 'type' => 'currency'], 'churned_mrr' => ['label' => 'Churned MRR', 'type' => 'currency'], 'contraction_mrr' => ['label' => 'Contraction MRR', 'type' => 'currency'], 'net_mrr_change' => ['label' => 'Net MRR Change', 'type' => 'currency'], 'ending_mrr' => ['label' => 'Ending MRR', 'type' => 'currency'], 'mrr_growth_rate' => ['label' => 'MRR Growth Rate', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'mrr-movement';
    }

    public function getDescription(): string
    {
        return 'Analyze MRR changes from new, expansion, churn, and contraction';
    }

    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();
        $periodBoundaries = [];
        foreach ($periods as $index => $periodStart) {
            $periodEnd = $this->getPeriodEnd($periodStart);
            $periodBoundaries[] = ['start' => $periodStart->toDateTimeString(), 'end' => $periodEnd->toDateTimeString(), 'order' => $index];
        }
        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }
        $concatExpr = $this->isSQLite() ? "(subscriptions.id || ':' || subscriptions.plan_id || ':' || COALESCE(subscriptions.quantity, 1))" : 'CONCAT(subscriptions.id, ":", subscriptions.plan_id, ":", COALESCE(subscriptions.quantity, 1))';
        $newGroupConcat = $this->dbGroupConcat("CASE\n                WHEN subscriptions.created_at >= periods.period_start\n                AND subscriptions.created_at <= periods.period_end\n                THEN {$concatExpr}\n            END", '|', ! $this->isSQLite());
        $churnedGroupConcat = $this->dbGroupConcat("CASE\n                WHEN subscriptions.canceled_at >= periods.period_start\n                AND subscriptions.canceled_at <= periods.period_end\n                THEN {$concatExpr}\n            END", '|', ! $this->isSQLite());

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin('subscriptions', function ($join) {
            $join->where(function ($q) {
                $q->whereBetween('subscriptions.created_at', [DB::raw('periods.period_start'), DB::raw('periods.period_end')])->orWhereBetween('subscriptions.canceled_at', [DB::raw('periods.period_start'), DB::raw('periods.period_end')]);
            });
        })->select(['periods.period_start', 'periods.period_end', 'periods.period_order', DB::raw("{$newGroupConcat} as new_subscription_data"), DB::raw("{$churnedGroupConcat} as churned_subscription_data")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $periodStart = Carbon::parse($row->period_start);
        $period = $this->formatPeriodLabel($periodStart);
        $startingMrr = $this->calculateMrrAtDate($periodStart->copy()->subDay());
        static $plans = null;
        if ($plans === null) {
            $plans = Plan::all()->keyBy('id');
        }
        $newMrr = 0;
        if (! empty($row->new_subscription_data)) {
            $data = explode('|', $row->new_subscription_data);
            if ($this->isSQLite()) {
                $data = array_unique($data);
            }
            foreach ($data as $item) {
                if (empty($item)) {
                    continue;
                }
                [$subId, $planId, $quantity] = explode(':', $item);
                $plan = $plans[$planId] ?? null;
                if ($plan) {
                    $newMrr += $this->getMonthlyPrice($plan) * (int) $quantity;
                }
            }
        }
        $churnedMrr = 0;
        if (! empty($row->churned_subscription_data)) {
            $data = explode('|', $row->churned_subscription_data);
            if ($this->isSQLite()) {
                $data = array_unique($data);
            }
            foreach ($data as $item) {
                if (empty($item)) {
                    continue;
                }
                [$subId, $planId, $quantity] = explode(':', $item);
                $plan = $plans[$planId] ?? null;
                if ($plan) {
                    $churnedMrr += $this->getMonthlyPrice($plan) * (int) $quantity;
                }
            }
        }
        $expansionMrr = 0;
        $contractionMrr = 0;
        $netMrrChange = $newMrr + $expansionMrr - $churnedMrr - $contractionMrr;
        $endingMrr = $startingMrr + $netMrrChange;
        $mrrGrowthRate = $startingMrr > 0 ? ($endingMrr - $startingMrr) / $startingMrr * 100 : 0;

        return ['period' => $period, 'starting_mrr' => (float) $startingMrr, 'new_mrr' => (float) $newMrr, 'expansion_mrr' => (float) $expansionMrr, 'churned_mrr' => (float) $churnedMrr, 'contraction_mrr' => (float) $contractionMrr, 'net_mrr_change' => (float) $netMrrChange, 'ending_mrr' => (float) $endingMrr, 'mrr_growth_rate' => (float) $mrrGrowthRate];
    }

    public function summarize(array $filters): array
    {
        $currentMrr = $this->calculateMrrAtDate(now());

        return ['current_mrr' => format_amount((float) $currentMrr)];
    }
}
