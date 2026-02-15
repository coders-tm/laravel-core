<?php

namespace Coderstm\Services\Reports\Economics;

use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class CacLtvReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'new_customers' => ['label' => 'New Customers', 'type' => 'number'], 'cac' => ['label' => 'CAC', 'type' => 'currency'], 'avg_ltv' => ['label' => 'Average LTV', 'type' => 'currency'], 'ltv_cac_ratio' => ['label' => 'LTV:CAC Ratio', 'type' => 'number']];

    public static function getType(): string
    {
        return 'cac-ltv';
    }

    public function getDescription(): string
    {
        return 'Analyze customer acquisition cost vs. lifetime value ratio';
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

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->leftJoin(DB::raw('subscriptions'), function ($join) {
            $join->whereRaw('subscriptions.created_at BETWEEN periods.period_start AND periods.period_end');
        })->leftJoin(DB::raw('orders'), function ($join) {
            $join->on('orders.customer_id', '=', 'subscriptions.user_id')->whereRaw("orders.payment_status = '".Order::STATUS_PAID."'");
        })->select(['periods.period_start', 'periods.period_end', 'periods.period_order', DB::raw('COUNT(DISTINCT subscriptions.user_id) as new_customers'), DB::raw('COALESCE(SUM(orders.grand_total), 0) as total_revenue')])->groupBy('periods.period_start', 'periods.period_end', 'periods.period_order')->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $newCustomers = (int) ($row->new_customers ?? 0);
        $totalRevenue = (float) ($row->total_revenue ?? 0);
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start)) ?? '';
        $marketingSpend = (float) config('coderstm.reports.marketing_spend', 0);
        $cac = $newCustomers > 0 ? $marketingSpend / $newCustomers : 0;
        $avgRevenuePerCustomer = $newCustomers > 0 ? $totalRevenue / $newCustomers : 0;
        $avgLtv = $avgRevenuePerCustomer * 24;
        $ltvCacRatio = $cac > 0 ? $avgLtv / $cac : 0;

        return ['period' => $period, 'new_customers' => $newCustomers, 'cac' => (float) $cac, 'avg_ltv' => (float) $avgLtv, 'ltv_cac_ratio' => (float) $ltvCacRatio];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('subscriptions')->leftJoin('orders', function ($join) {
            $join->on('orders.customer_id', '=', 'subscriptions.user_id')->where('orders.payment_status', '=', Order::STATUS_PAID);
        })->whereBetween('subscriptions.created_at', [$filters['from'], $filters['to']])->selectRaw('
                COUNT(DISTINCT subscriptions.user_id) as new_customers,
                COALESCE(SUM(orders.grand_total), 0) as total_revenue
            ')->first();
        $newCustomers = (int) ($stats->new_customers ?? 0);
        $totalRevenue = (float) ($stats->total_revenue ?? 0);
        $marketingSpend = (float) config('coderstm.reports.marketing_spend', 0);
        $cac = $newCustomers > 0 ? $marketingSpend / $newCustomers : 0;
        $avgRevenuePerCustomer = $newCustomers > 0 ? $totalRevenue / $newCustomers : 0;
        $avgLtv = $avgRevenuePerCustomer * 24;
        $ltvCacRatio = $cac > 0 ? $avgLtv / $cac : 0;

        return ['total_new_customers' => $newCustomers, 'total_marketing_spend' => format_amount($marketingSpend), 'average_cac' => format_amount($cac), 'average_ltv' => format_amount($avgLtv), 'ltv_cac_ratio' => round($ltvCacRatio, 2)];
    }
}
