<?php

namespace Coderstm\Services\Reports\Orders;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Refund Analysis Report - Refund metrics and patterns.
 *
 * Uses Pattern 1 (Time-Series) with UNION ALL period boundaries and single query.
 * All metrics calculated in one pass without N+1 queries.
 */
class RefundAnalysisReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'period' => ['label' => 'Period', 'type' => 'text'],
        'total_refunds' => ['label' => 'Total Refunds', 'type' => 'number'],
        'full_refunds' => ['label' => 'Full Refunds', 'type' => 'number'],
        'partial_refunds' => ['label' => 'Partial Refunds', 'type' => 'number'],
        'refund_amount' => ['label' => 'Refund Amount', 'type' => 'currency'],
        'refund_rate' => ['label' => 'Refund Rate', 'type' => 'percentage'],
        'avg_refund_amount' => ['label' => 'Avg Refund Amount', 'type' => 'currency'],
        'orders_with_refunds' => ['label' => 'Orders With Refunds', 'type' => 'number'],
        'total_orders' => ['label' => 'Total Orders', 'type' => 'number'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'refund-analysis';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Analyze refund trends and patterns';
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();
        $periodBoundaries = [];

        foreach ($periods as $index => $periodStart) {
            $periodEnd = $this->getPeriodEnd($periodStart);
            $periodBoundaries[] = [
                'start' => $periodStart->toDateTimeString(),
                'end' => $periodEnd->toDateTimeString(),
                'order' => $index,
            ];
        }

        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }

        $ordersQuery = Coderstm::$orderModel::query()->select('*');

        // Single query with all metrics
        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))
            ->mergeBindings($periodQuery)
            ->leftJoinSub($ordersQuery, 'orders', function ($join) {
                $join->whereRaw('orders.created_at BETWEEN periods.period_start AND periods.period_end');
            })
            ->select([
                'periods.period_start',
                'periods.period_order',
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('COUNT(CASE WHEN orders.refund_total > 0 THEN 1 END) as orders_with_refunds'),
                DB::raw('COALESCE(SUM(orders.refund_total), 0) as refund_amount'),
                DB::raw('COUNT(CASE WHEN orders.refund_total >= orders.grand_total AND orders.refund_total > 0 THEN 1 END) as full_refunds'),
                DB::raw('COUNT(CASE WHEN orders.refund_total > 0 AND orders.refund_total < orders.grand_total THEN 1 END) as partial_refunds'),
            ])
            ->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')
            ->orderBy('periods.period_order');
    }

    /**
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        // Compute period label from period_start timestamp
        $period = $this->formatPeriodLabel(Carbon::parse($row->period_start));

        $totalOrders = (int) ($row->total_orders ?? 0);
        $ordersWithRefunds = (int) ($row->orders_with_refunds ?? 0);
        $refundAmount = (float) ($row->refund_amount ?? 0);

        $refundRate = $totalOrders > 0
            ? ($ordersWithRefunds / $totalOrders) * 100
            : 0;

        $avgRefundAmount = $ordersWithRefunds > 0
            ? $refundAmount / $ordersWithRefunds
            : 0;

        return [
            'period' => $period,
            'total_refunds' => $ordersWithRefunds, // Refunds = orders with refunds
            'full_refunds' => (int) ($row->full_refunds ?? 0),
            'partial_refunds' => (int) ($row->partial_refunds ?? 0),
            'refund_amount' => $refundAmount,
            'refund_rate' => (float) $refundRate,
            'avg_refund_amount' => (float) $avgRefundAmount,
            'orders_with_refunds' => $ordersWithRefunds,
            'total_orders' => $totalOrders,
        ];
    }

    /**
     * Calculate summary statistics.
     *
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $stats = Coderstm::$orderModel::query()->toBase()
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->selectRaw('
                COUNT(*) as total_orders,
                COUNT(CASE WHEN refund_total > 0 THEN 1 END) as orders_with_refunds,
                COALESCE(SUM(refund_total), 0) as refund_amount
            ')
            ->first();

        if (! $stats) {
            return [
                'total_orders' => 0,
                'orders_with_refunds' => 0,
                'refund_amount' => format_amount(0),
                'refund_rate' => (float) 0,
            ];
        }

        $totalOrders = (int) $stats->total_orders;
        $ordersWithRefunds = (int) $stats->orders_with_refunds;

        return [
            'total_orders' => $totalOrders,
            'orders_with_refunds' => $ordersWithRefunds,
            'refund_amount' => format_amount($stats->refund_amount),
            'refund_rate' => $totalOrders > 0 ? ($ordersWithRefunds / $totalOrders) * 100 : 0,
        ];
    }
}
