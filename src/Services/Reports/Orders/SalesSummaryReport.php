<?php

namespace Coderstm\Services\Reports\Orders;

use Coderstm\Models\Payment;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class SalesSummaryReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'total_orders' => ['label' => 'Total Orders', 'type' => 'number'], 'gmv' => ['label' => 'GMV', 'type' => 'currency'], 'net_revenue' => ['label' => 'Net Revenue', 'type' => 'currency'], 'discount_total' => ['label' => 'Discount Total', 'type' => 'currency'], 'tax_total' => ['label' => 'Tax Total', 'type' => 'currency'], 'shipping_total' => ['label' => 'Shipping Total', 'type' => 'currency'], 'refund_total' => ['label' => 'Refund Total', 'type' => 'currency'], 'paid_total' => ['label' => 'Paid Total', 'type' => 'currency'], 'aov' => ['label' => 'AOV', 'type' => 'currency'], 'completed_orders' => ['label' => 'Completed Orders', 'type' => 'number'], 'cancelled_orders' => ['label' => 'Cancelled Orders', 'type' => 'number']];

    public static function getType(): string
    {
        return 'sales-summary';
    }

    public function getDescription(): string
    {
        return 'Overview of total sales, revenue, and order statistics';
    }

    public function validate(array $input): array
    {
        $validated = validator($input, ['payment_status' => 'nullable|string|in:paid,unpaid,pending,failed,refunded', 'fulfillment_status' => 'nullable|string|in:pending,processing,shipped,delivered,cancelled', 'order_status' => 'nullable|string|in:pending,processing,completed,cancelled'])->validate();

        return parent::validate($validated);
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
        $completedStatus = Payment::STATUS_COMPLETED;

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('orders'), function ($join) {
            $join->whereRaw('orders.created_at BETWEEN periods.period_start AND periods.period_end');
        })->select(['periods.period_start', 'periods.period_order', DB::raw('COUNT(orders.id) as total_orders'), DB::raw('COALESCE(SUM(orders.grand_total), 0) as gmv'), DB::raw('COALESCE(SUM(orders.discount_total), 0) as discount_total'), DB::raw('COALESCE(SUM(orders.tax_total), 0) as tax_total'), DB::raw('COALESCE(SUM(orders.shipping_total), 0) as shipping_total'), DB::raw('COALESCE(SUM(orders.refund_total), 0) as refund_total'), DB::raw('COALESCE(SUM(orders.paid_total), 0) as paid_total'), DB::raw("COUNT(CASE WHEN orders.payment_status = '{$completedStatus}' THEN 1 END) as completed_orders"), DB::raw("COUNT(CASE WHEN orders.status IN ('canceled', 'cancelled') THEN 1 END) as cancelled_orders")])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));
        $totalOrders = (int) ($row->total_orders ?? 0);
        $gmv = (float) ($row->gmv ?? 0);
        $discountTotal = (float) ($row->discount_total ?? 0);
        $refundTotal = (float) ($row->refund_total ?? 0);
        $netRevenue = $gmv - $refundTotal - $discountTotal;
        $aov = $totalOrders > 0 ? $gmv / $totalOrders : 0;

        return ['period' => $period, 'total_orders' => $totalOrders, 'gmv' => $gmv, 'net_revenue' => (float) $netRevenue, 'discount_total' => $discountTotal, 'tax_total' => (float) ($row->tax_total ?? 0), 'shipping_total' => (float) ($row->shipping_total ?? 0), 'refund_total' => $refundTotal, 'paid_total' => (float) ($row->paid_total ?? 0), 'aov' => (float) $aov, 'completed_orders' => (int) ($row->completed_orders ?? 0), 'cancelled_orders' => (int) ($row->cancelled_orders ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('orders')->whereBetween('created_at', [$filters['from'], $filters['to']])->selectRaw('
                COUNT(*) as total_orders,
                COALESCE(SUM(grand_total), 0) as gmv,
                COALESCE(SUM(grand_total - COALESCE(refund_total, 0) - COALESCE(discount_total, 0)), 0) as net_revenue,
                COALESCE(SUM(discount_total), 0) as discount_total,
                COALESCE(SUM(refund_total), 0) as refund_total
            ')->first();
        if (! $stats) {
            return ['total_orders' => 0, 'gmv' => format_amount(0), 'net_revenue' => format_amount(0), 'discount_total' => format_amount(0), 'refund_total' => format_amount(0), 'aov' => format_amount(0)];
        }
        $totalOrders = (int) $stats->total_orders;
        $gmv = (float) $stats->gmv;

        return ['total_orders' => $totalOrders, 'gmv' => format_amount($gmv), 'net_revenue' => format_amount($stats->net_revenue), 'discount_total' => format_amount($stats->discount_total), 'refund_total' => format_amount($stats->refund_total), 'aov' => format_amount($totalOrders > 0 ? $gmv / $totalOrders : 0)];
    }
}
