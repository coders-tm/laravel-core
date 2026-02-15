<?php

namespace Coderstm\Services\Reports\Coupons;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class DiscountImpactReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'total_orders' => ['label' => 'Total Orders', 'type' => 'number'], 'orders_with_discount' => ['label' => 'Orders with Discount', 'type' => 'number'], 'discount_rate' => ['label' => 'Discount Rate', 'type' => 'percentage'], 'gross_revenue' => ['label' => 'Gross Revenue', 'type' => 'currency'], 'total_discounts' => ['label' => 'Total Discounts', 'type' => 'currency'], 'net_revenue' => ['label' => 'Net Revenue', 'type' => 'currency'], 'avg_discount_per_order' => ['label' => 'Avg Discount/Order', 'type' => 'currency'], 'discount_percentage_of_revenue' => ['label' => 'Discount % of Revenue', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'discount-impact';
    }

    public function getDescription(): string
    {
        return 'Measure how discounts affect revenue and profitability';
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

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('orders'), function ($join) {
            $join->whereRaw('orders.created_at BETWEEN periods.period_start AND periods.period_end');
        })->leftJoin(DB::raw('discount_lines'), function ($join) {
            $join->on('discount_lines.discountable_id', '=', 'orders.id')->whereRaw("discount_lines.discountable_type LIKE '%Order%'");
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(DISTINCT orders.id) as total_orders'), DB::raw('COUNT(DISTINCT CASE WHEN discount_lines.id IS NOT NULL THEN orders.id END) as orders_with_discount'), DB::raw('COALESCE(SUM(orders.grand_total), 0) + COALESCE(SUM(discount_lines.value), 0) as gross_revenue'), DB::raw('COALESCE(SUM(discount_lines.value), 0) as total_discounts'), DB::raw('COALESCE(SUM(orders.grand_total), 0) as net_revenue')])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $totalOrders = (int) ($row->total_orders ?? 0);
        $ordersWithDiscount = (int) ($row->orders_with_discount ?? 0);
        $grossRevenue = (float) ($row->gross_revenue ?? 0);
        $totalDiscounts = (float) ($row->total_discounts ?? 0);
        $netRevenue = (float) ($row->net_revenue ?? 0);
        $discountRate = $totalOrders > 0 ? $ordersWithDiscount / $totalOrders * 100 : 0;
        $avgDiscountPerOrder = $ordersWithDiscount > 0 ? $totalDiscounts / $ordersWithDiscount : 0;
        $discountPercentage = $grossRevenue > 0 ? $totalDiscounts / $grossRevenue * 100 : 0;

        return ['period' => $period, 'total_orders' => $totalOrders, 'orders_with_discount' => $ordersWithDiscount, 'discount_rate' => (float) $discountRate, 'gross_revenue' => $grossRevenue, 'total_discounts' => $totalDiscounts, 'net_revenue' => $netRevenue, 'avg_discount_per_order' => (float) $avgDiscountPerOrder, 'discount_percentage_of_revenue' => (float) $discountPercentage];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('orders')->leftJoin(DB::raw('discount_lines'), function ($join) {
            $join->on('discount_lines.discountable_id', '=', 'orders.id')->where('discount_lines.discountable_type', 'like', '%Order%');
        })->whereBetween('orders.created_at', [$filters['from'], $filters['to']])->selectRaw('
                COUNT(DISTINCT orders.id) as total_orders,
                COUNT(DISTINCT CASE WHEN discount_lines.id IS NOT NULL THEN orders.id END) as orders_with_discount,
                COALESCE(SUM(orders.grand_total), 0) + COALESCE(SUM(discount_lines.value), 0) as gross_revenue,
                COALESCE(SUM(discount_lines.value), 0) as total_discounts,
                COALESCE(SUM(orders.grand_total), 0) as net_revenue
            ')->first();
        if (! $stats) {
            return ['total_orders' => 0, 'orders_with_discount' => 0, 'total_gross_revenue' => format_amount(0), 'total_discounts' => format_amount(0), 'total_net_revenue' => format_amount(0), 'discount_rate' => (float) 0];
        }
        $totalOrders = (int) $stats->total_orders;
        $ordersWithDiscount = (int) $stats->orders_with_discount;

        return ['total_orders' => $totalOrders, 'orders_with_discount' => $ordersWithDiscount, 'total_gross_revenue' => format_amount($stats->gross_revenue), 'total_discounts' => format_amount($stats->total_discounts), 'total_net_revenue' => format_amount($stats->net_revenue), 'discount_rate' => $totalOrders > 0 ? $ordersWithDiscount / $totalOrders * 100 : 0];
    }
}
