<?php

namespace Coderstm\Services\Reports\Revenue;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class ActiveSubscriptionsTimeReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'active_subscriptions' => ['label' => 'Active Subscriptions', 'type' => 'number'], 'new_subscriptions' => ['label' => 'New Subscriptions', 'type' => 'number'], 'canceled_subscriptions' => ['label' => 'Canceled Subscriptions', 'type' => 'number'], 'net_change' => ['label' => 'Net Change', 'type' => 'number'], 'growth_rate' => ['label' => 'Growth Rate', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'active-subscriptions-time';
    }

    public function getDescription(): string
    {
        return 'Track active subscription count and growth over time';
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
        $metricsQuery = DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->crossJoin('subscriptions')->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(DISTINCT CASE
                    WHEN subscriptions.created_at <= periods.period_end
                    AND (subscriptions.canceled_at IS NULL OR subscriptions.canceled_at > periods.period_end)
                    AND (subscriptions.expires_at IS NULL OR subscriptions.expires_at > periods.period_end)
                    THEN subscriptions.id
                END) as active_subscriptions'), DB::raw('COUNT(DISTINCT CASE
                    WHEN subscriptions.created_at >= periods.period_start
                    AND subscriptions.created_at <= periods.period_end
                    THEN subscriptions.id
                END) as new_subscriptions'), DB::raw('COUNT(DISTINCT CASE
                    WHEN subscriptions.canceled_at IS NOT NULL
                    AND subscriptions.canceled_at >= periods.period_start
                    AND subscriptions.canceled_at <= periods.period_end
                    THEN subscriptions.id
                END) as canceled_subscriptions')])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');

        return DB::table(DB::raw("({$metricsQuery->toSql()}) as metrics"))->mergeBindings($metricsQuery)->select(['metrics.period_start', 'metrics.period_order', 'metrics.active_subscriptions', 'metrics.new_subscriptions', 'metrics.canceled_subscriptions', DB::raw('CASE
                    WHEN LAG(metrics.active_subscriptions) OVER (ORDER BY metrics.period_order) IS NULL
                         OR LAG(metrics.active_subscriptions) OVER (ORDER BY metrics.period_order) = 0
                    THEN 0
                    ELSE ((metrics.active_subscriptions - LAG(metrics.active_subscriptions) OVER (ORDER BY metrics.period_order)) * 100.0
                          / LAG(metrics.active_subscriptions) OVER (ORDER BY metrics.period_order))
                END as growth_rate')])->orderBy('metrics.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $netChange = ($row->new_subscriptions ?? 0) - ($row->canceled_subscriptions ?? 0);

        return ['period' => $period, 'active_subscriptions' => (int) ($row->active_subscriptions ?? 0), 'new_subscriptions' => (int) ($row->new_subscriptions ?? 0), 'canceled_subscriptions' => (int) ($row->canceled_subscriptions ?? 0), 'net_change' => $netChange, 'growth_rate' => (float) ($row->growth_rate ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();
        $currentActive = DB::table('subscriptions')->whereNull('canceled_at')->where(function ($q) use ($now) {
            $q->whereNull('expires_at')->orWhereRaw('expires_at > ?', [$now]);
        })->count();

        return ['current_active' => (int) $currentActive];
    }
}
