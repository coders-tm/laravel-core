<?php

namespace Coderstm\Services\Reports\Retention;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class MemberRetentionReport extends AbstractReport
{
    protected array $retentionMonths = [1, 2, 3, 6, 12];

    protected array $columns = ['cohort' => ['label' => 'Cohort Period', 'type' => 'text'], 'initial_count' => ['label' => 'Cohort Size', 'type' => 'number'], 'month_1' => ['label' => 'Month 1', 'type' => 'percentage'], 'month_2' => ['label' => 'Month 2', 'type' => 'percentage'], 'month_3' => ['label' => 'Month 3', 'type' => 'percentage'], 'month_6' => ['label' => 'Month 6', 'type' => 'percentage'], 'month_12' => ['label' => 'Month 12', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'member-retention';
    }

    public function getDescription(): string
    {
        return 'Cohort analysis of member retention over time';
    }

    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();
        $periodBoundaries = [];
        foreach ($periods as $periodStart) {
            $cohortStart = \Carbon\Carbon::instance($periodStart)->startOfMonth();
            $cohortEnd = \Carbon\Carbon::instance($periodStart)->endOfMonth();
            $periodBoundaries[] = ['start' => $cohortStart->toDateTimeString(), 'end' => $cohortEnd->toDateTimeString(), 'order' => count($periodBoundaries)];
        }
        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }
        $now = now()->toDateTimeString();
        $selects = [DB::raw('
                (
                    SELECT COUNT(DISTINCT user_id)
                    FROM subscriptions
                    WHERE created_at >= periods.period_start
                    AND created_at <= periods.period_end
                ) as cohort_label
            '), 'periods.period_start', 'periods.period_end', 'periods.period_order', DB::raw('
                (
                    SELECT COUNT(DISTINCT user_id)
                    FROM subscriptions
                    WHERE created_at >= periods.period_start
                    AND created_at <= periods.period_end
                ) as initial_count
            ')];
        foreach ($this->retentionMonths as $months) {
            $checkDate = $this->dbAddMonths('periods.period_start', $months);
            $selects[] = $this->buildRetentionCalculation($months, $checkDate, $now);
        }

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->select($selects)->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    protected function buildRetentionCalculation(int $months, string $checkDate, string $now): \Illuminate\Database\Query\Expression
    {
        $checkDateEscaped = str_replace("'", "''", $checkDate);
        $nowEscaped = str_replace("'", "''", $now);
        $sql = "\n            CASE\n                WHEN {$checkDate} > '{$nowEscaped}' THEN 0\n                ELSE (\n                    SELECT COUNT(DISTINCT cohort_subs.user_id) * 100.0 / NULLIF(\n                        (SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE created_at >= periods.period_start AND created_at <= periods.period_end),\n                        0\n                    )\n                    FROM subscriptions as cohort_subs\n                    WHERE cohort_subs.created_at >= periods.period_start\n                    AND cohort_subs.created_at <= periods.period_end\n                    AND EXISTS (\n                        SELECT 1\n                        FROM subscriptions as check_subs\n                        WHERE check_subs.user_id = cohort_subs.user_id\n                        AND check_subs.created_at <= {$checkDate}\n                        AND (check_subs.canceled_at IS NULL OR check_subs.canceled_at > {$checkDate})\n                        AND (check_subs.expires_at IS NULL OR check_subs.expires_at > {$checkDate})\n                    )\n                )\n            END as month_{$months}\n        ";

        return DB::raw($sql);
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function toRow($row): array
    {
        $cohort = \Carbon\Carbon::parse($row->period_start)->format('Y-m');

        return ['cohort' => $cohort, 'initial_count' => (int) ($row->initial_count ?? 0), 'month_1' => (float) ($row->month_1 ?? 0), 'month_2' => (float) ($row->month_2 ?? 0), 'month_3' => (float) ($row->month_3 ?? 0), 'month_6' => (float) ($row->month_6 ?? 0), 'month_12' => (float) ($row->month_12 ?? 0)];
    }

    public function summarize(array $filters): array
    {
        return [];
    }
}
