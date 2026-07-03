<?php

namespace Coderstm\Services\Reports\Retention;

use Carbon\Carbon;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Member Retention Cohort Report
 *
 * Tracks cohort retention rates at 1, 2, 3, 6, and 12 months.
 * Uses correlated subqueries for efficient retention calculations.
 * Database-agnostic for MySQL, PostgreSQL, SQLite, and SQL Server.
 */
class MemberRetentionReport extends AbstractReport
{
    /**
     * Months to track for retention
     */
    protected array $retentionMonths = [1, 2, 3, 6, 12];

    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'cohort' => ['label' => 'Cohort Period', 'type' => 'text'],
        'initial_count' => ['label' => 'Cohort Size', 'type' => 'number'],
        'month_1' => ['label' => 'Month 1', 'type' => 'percentage'],
        'month_2' => ['label' => 'Month 2', 'type' => 'percentage'],
        'month_3' => ['label' => 'Month 3', 'type' => 'percentage'],
        'month_6' => ['label' => 'Month 6', 'type' => 'percentage'],
        'month_12' => ['label' => 'Month 12', 'type' => 'percentage'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'member-retention';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Cohort analysis of member retention over time';
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();

        // Build period boundaries array
        $periodBoundaries = [];
        foreach ($periods as $periodStart) {
            $cohortStart = Carbon::instance($periodStart)->startOfMonth();
            $cohortEnd = Carbon::instance($periodStart)->endOfMonth();
            $periodBoundaries[] = [
                'start' => $cohortStart->toDateTimeString(),
                'end' => $cohortEnd->toDateTimeString(),
                'order' => count($periodBoundaries),
            ];
        }

        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }

        // Pre-calculate check dates for cohorts
        $now = now()->toDateTimeString();

        // Build retention month calculations dynamically
        $selects = [
            DB::raw('
                (
                    SELECT COUNT(DISTINCT user_id)
                    FROM subscriptions
                    WHERE created_at >= periods.period_start
                    AND created_at <= periods.period_end
                ) as cohort_label
            '),
            'periods.period_start',
            'periods.period_end',
            'periods.period_order',
            DB::raw('
                (
                    SELECT COUNT(DISTINCT user_id)
                    FROM subscriptions
                    WHERE created_at >= periods.period_start
                    AND created_at <= periods.period_end
                ) as initial_count
            '),
        ];

        // Add retention calculations for each month
        foreach ($this->retentionMonths as $months) {
            $checkDate = $this->dbAddMonths('periods.period_start', $months);
            $selects[] = $this->buildRetentionCalculation($months, $checkDate, $now);
        }

        // Build the main query
        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))
            ->mergeBindings($periodQuery)
            ->select($selects)
            ->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')
            ->orderBy('periods.period_order');
    }

    /**
     * Build a retention calculation for a specific month.
     *
     * @param  int  $months  Number of months after cohort start
     * @param  string  $checkDate  Database-agnostic expression for the check date
     * @param  string  $now  Current timestamp as string
     */
    protected function buildRetentionCalculation(int $months, string $checkDate, string $now): Expression
    {
        // Escape the check date and now for use in raw SQL
        $checkDateEscaped = str_replace("'", "''", $checkDate);
        $nowEscaped = str_replace("'", "''", $now);

        $sql = "
            CASE
                WHEN {$checkDate} > '{$nowEscaped}' THEN 0
                ELSE (
                    SELECT COUNT(DISTINCT cohort_subs.user_id) * 100.0 / NULLIF(
                        (SELECT COUNT(DISTINCT user_id) FROM subscriptions WHERE created_at >= periods.period_start AND created_at <= periods.period_end),
                        0
                    )
                    FROM subscriptions as cohort_subs
                    WHERE cohort_subs.created_at >= periods.period_start
                    AND cohort_subs.created_at <= periods.period_end
                    AND EXISTS (
                        SELECT 1
                        FROM subscriptions as check_subs
                        WHERE check_subs.user_id = cohort_subs.user_id
                        AND check_subs.created_at <= {$checkDate}
                        AND (check_subs.canceled_at IS NULL OR check_subs.canceled_at > {$checkDate})
                        AND (check_subs.expires_at IS NULL OR check_subs.expires_at > {$checkDate})
                    )
                )
            END as month_{$months}
        ";

        return DB::raw($sql);
    }

    /**
     * Define column types for frontend formatting.
     *
     * {@inheritdoc}
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * Transform row to array with raw values.
     *
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        // Compute cohort label from period_start
        $cohort = Carbon::parse($row->period_start)->format('Y-m');

        return [
            'cohort' => $cohort,
            'initial_count' => (int) ($row->initial_count ?? 0),
            'month_1' => (float) ($row->month_1 ?? 0),
            'month_2' => (float) ($row->month_2 ?? 0),
            'month_3' => (float) ($row->month_3 ?? 0),
            'month_6' => (float) ($row->month_6 ?? 0),
            'month_12' => (float) ($row->month_12 ?? 0),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        return [];
    }
}
