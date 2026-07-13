<?php

namespace Coderstm\Services\Reports\Orders;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Fulfillment Analysis Report - Order fulfillment and delivery performance.
 *
 * Uses Pattern 1 (Time-Series) with UNION ALL period boundaries and single query.
 * All metrics calculated in one pass without N+1 queries.
 */
class FulfillmentAnalysisReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'period' => ['label' => 'Period', 'type' => 'text'],
        'total_orders' => ['label' => 'Total Orders', 'type' => 'number'],
        'pending_fulfillment' => ['label' => 'Pending Fulfillment', 'type' => 'number'],
        'processing' => ['label' => 'Processing', 'type' => 'number'],
        'shipped' => ['label' => 'Shipped', 'type' => 'number'],
        'delivered' => ['label' => 'Delivered', 'type' => 'number'],
        'cancelled' => ['label' => 'Cancelled', 'type' => 'number'],
        'fulfillment_rate' => ['label' => 'Fulfillment Rate', 'type' => 'percentage'],
        'avg_fulfillment_time' => ['label' => 'Avg Fulfillment Time (hours)', 'type' => 'number'],
        'avg_delivery_time' => ['label' => 'Avg Delivery Time (days)', 'type' => 'number'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'fulfillment-analysis';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Monitor order fulfillment and delivery performance';
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

        // Database-agnostic date diff for time calculations
        $fulfillmentHours = $this->dbDateDiff('orders.shipped_at', 'orders.created_at');
        $deliveryDays = $this->dbDateDiff('orders.delivered_at', 'orders.shipped_at');

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
                DB::raw("COUNT(CASE WHEN orders.fulfillment_status = 'pending' OR orders.fulfillment_status IS NULL THEN 1 END) as pending_fulfillment"),
                DB::raw("COUNT(CASE WHEN orders.fulfillment_status = 'processing' THEN 1 END) as processing"),
                DB::raw("COUNT(CASE WHEN orders.fulfillment_status = 'shipped' THEN 1 END) as shipped"),
                DB::raw("COUNT(CASE WHEN orders.fulfillment_status = 'delivered' THEN 1 END) as delivered"),
                DB::raw("COUNT(CASE WHEN orders.fulfillment_status = 'cancelled' OR orders.status = 'cancelled' OR orders.status = 'canceled' THEN 1 END) as cancelled"),
                DB::raw("COALESCE(AVG(CASE WHEN orders.shipped_at IS NOT NULL THEN ({$fulfillmentHours}) * 24 END), 0) as avg_fulfillment_time"),
                DB::raw("COALESCE(AVG(CASE WHEN orders.delivered_at IS NOT NULL AND orders.shipped_at IS NOT NULL THEN {$deliveryDays} END), 0) as avg_delivery_time"),
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
        $shipped = (int) ($row->shipped ?? 0);
        $delivered = (int) ($row->delivered ?? 0);

        $fulfillmentRate = $totalOrders > 0
            ? (($shipped + $delivered) / $totalOrders) * 100
            : 0;

        return [
            'period' => $period,
            'total_orders' => $totalOrders,
            'pending_fulfillment' => (int) ($row->pending_fulfillment ?? 0),
            'processing' => (int) ($row->processing ?? 0),
            'shipped' => $shipped,
            'delivered' => $delivered,
            'cancelled' => (int) ($row->cancelled ?? 0),
            'fulfillment_rate' => (float) $fulfillmentRate,
            'avg_fulfillment_time' => (float) ($row->avg_fulfillment_time ?? 0),
            'avg_delivery_time' => (float) ($row->avg_delivery_time ?? 0),
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
            ->selectRaw("
                COUNT(*) as total_orders,
                COUNT(CASE WHEN fulfillment_status = 'delivered' THEN 1 END) as delivered,
                COUNT(CASE WHEN fulfillment_status = 'shipped' THEN 1 END) as shipped
            ")
            ->first();

        if (! $stats) {
            return [
                'total_orders' => 0,
                'delivered' => 0,
                'shipped' => 0,
                'fulfillment_rate' => (float) 0,
            ];
        }

        $totalOrders = (int) $stats->total_orders;
        $delivered = (int) $stats->delivered;
        $shipped = (int) $stats->shipped;

        return [
            'total_orders' => $totalOrders,
            'delivered' => $delivered,
            'shipped' => $shipped,
            'fulfillment_rate' => $totalOrders > 0 ? (($shipped + $delivered) / $totalOrders) * 100 : 0,
        ];
    }
}
