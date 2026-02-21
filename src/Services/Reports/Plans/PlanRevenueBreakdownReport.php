<?php

namespace Coderstm\Services\Reports\Plans;

use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class PlanRevenueBreakdownReport extends AbstractReport
{
    protected array $columns = ['plan_id' => ['label' => 'Plan ID', 'type' => 'number'], 'plan_name' => ['label' => 'Plan Name', 'type' => 'text'], 'price' => ['label' => 'Price', 'type' => 'currency'], 'gross_revenue' => ['label' => 'Gross Revenue', 'type' => 'currency'], 'discounts_applied' => ['label' => 'Discounts Applied', 'type' => 'currency'], 'refunds' => ['label' => 'Refunds', 'type' => 'currency'], 'net_revenue' => ['label' => 'Net Revenue', 'type' => 'currency'], 'avg_revenue_per_sub' => ['label' => 'Avg Revenue/Sub', 'type' => 'currency'], 'growth_rate' => ['label' => 'Growth Rate', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'plan-revenue-breakdown';
    }

    public function getDescription(): string
    {
        return 'Detailed revenue analysis by subscription plan';
    }

    public function query(array $filters)
    {
        return DB::table('plans')->leftJoin('subscriptions', function ($join) use ($filters) {
            $join->on('subscriptions.plan_id', '=', 'plans.id')->whereBetween('subscriptions.created_at', [$filters['from'], $filters['to']]);
        })->leftJoin('orders', function ($join) {
            $join->on('orders.orderable_id', '=', 'subscriptions.id')->where('orders.orderable_type', 'like', '%Subscription');
        })->select(['plans.id as plan_id', 'plans.label as plan_name', 'plans.price', DB::raw("COALESCE(SUM(CASE WHEN orders.payment_status = '".Order::STATUS_PAID."' THEN orders.grand_total END), 0) as gross_revenue"), DB::raw("COALESCE(SUM(CASE WHEN orders.payment_status = '".Order::STATUS_PAID."' THEN COALESCE(orders.discount_total, 0) END), 0) as discounts_applied"), DB::raw("COALESCE(SUM(CASE WHEN orders.payment_status = '".Order::STATUS_REFUNDED."' THEN orders.grand_total END), 0) as refunds"), DB::raw('COUNT(DISTINCT subscriptions.id) as subscription_count')])->groupBy('plans.id', 'plans.label', 'plans.price')->orderBy('plans.label');
    }

    public function toRow($row): array
    {
        $grossRevenue = (float) ($row->gross_revenue ?? 0);
        $discountsApplied = (float) ($row->discounts_applied ?? 0);
        $refunds = (float) ($row->refunds ?? 0);
        $netRevenue = $grossRevenue - $discountsApplied - $refunds;
        $subCount = (int) ($row->subscription_count ?? 0);
        $avgRevenuePerSub = $subCount > 0 ? $netRevenue / $subCount : 0;

        return ['plan_id' => (int) $row->plan_id, 'plan_name' => $row->plan_name ?? '', 'price' => (float) ($row->price ?? 0), 'gross_revenue' => $grossRevenue, 'discounts_applied' => $discountsApplied, 'refunds' => $refunds, 'net_revenue' => (float) $netRevenue, 'avg_revenue_per_sub' => (float) $avgRevenuePerSub, 'growth_rate' => (float) 0];
    }

    public function summarize(array $filters): array
    {
        $summary = DB::table('orders')->whereBetween('created_at', [$filters['from'], $filters['to']])->where('orderable_type', 'like', '%Subscription')->select([DB::raw("COALESCE(SUM(CASE WHEN payment_status = '".Order::STATUS_PAID."' THEN grand_total END), 0) as gross_revenue"), DB::raw("COALESCE(SUM(CASE WHEN payment_status = '".Order::STATUS_PAID."' THEN COALESCE(discount_total, 0) END), 0) as discounts_applied"), DB::raw("COALESCE(SUM(CASE WHEN payment_status = '".Order::STATUS_REFUNDED."' THEN grand_total END), 0) as refunds")])->first();
        if (! $summary) {
            return ['total_gross_revenue' => format_amount(0), 'total_discounts' => format_amount(0), 'total_refunds' => format_amount(0), 'total_net_revenue' => format_amount(0)];
        }
        $grossRevenue = (float) ($summary->gross_revenue ?? 0);
        $discountsApplied = (float) ($summary->discounts_applied ?? 0);
        $refunds = (float) ($summary->refunds ?? 0);

        return ['total_gross_revenue' => format_amount($grossRevenue), 'total_discounts' => format_amount($discountsApplied), 'total_refunds' => format_amount($refunds), 'total_net_revenue' => format_amount($grossRevenue - $discountsApplied - $refunds)];
    }
}
