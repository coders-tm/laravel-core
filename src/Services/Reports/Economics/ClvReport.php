<?php

namespace Coderstm\Services\Reports\Economics;

use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class ClvReport extends AbstractReport
{
    protected array $columns = ['user_id' => ['label' => 'User ID', 'type' => 'number'], 'user_email' => ['label' => 'Email', 'type' => 'text'], 'first_order' => ['label' => 'First Order', 'type' => 'text'], 'months_active' => ['label' => 'Months Active', 'type' => 'number'], 'total_revenue' => ['label' => 'Total Revenue', 'type' => 'currency'], 'avg_monthly_revenue' => ['label' => 'Avg Monthly Revenue', 'type' => 'currency'], 'estimated_clv' => ['label' => 'Estimated CLV', 'type' => 'currency'], 'order_count' => ['label' => 'Total Orders', 'type' => 'number']];

    public static function getType(): string
    {
        return 'clv';
    }

    public function getDescription(): string
    {
        return 'Calculate customer lifetime value and revenue projections';
    }

    public function query(array $filters)
    {
        $userTable = (new Coderstm::$userModel)->getTable();
        $now = now()->toDateTimeString();
        $monthsDiffExpression = $this->dbDateDiffMonths("'{$now}'", 'MIN(orders.created_at)');

        return DB::table($userTable)->join('orders', 'orders.customer_id', '=', "{$userTable}.id")->where('orders.payment_status', Order::STATUS_PAID)->whereBetween('orders.created_at', [$filters['from'], $filters['to']])->select(["{$userTable}.id as user_id", "{$userTable}.email as user_email", DB::raw('MIN(orders.created_at) as first_order_date'), DB::raw("{$monthsDiffExpression} as months_active"), DB::raw('COALESCE(SUM(orders.grand_total), 0) as total_revenue'), DB::raw('COUNT(orders.id) as order_count')])->groupBy("{$userTable}.id", "{$userTable}.email")->orderBy("{$userTable}.id");
    }

    public function toRow($row): array
    {
        $monthsActive = max(1, (int) ($row->months_active ?? 1));
        $totalRevenue = (float) ($row->total_revenue ?? 0);
        $avgMonthlyRevenue = $totalRevenue / $monthsActive;
        $expectedLifetimeMonths = 24;
        $estimatedClv = $avgMonthlyRevenue * $expectedLifetimeMonths;

        return ['user_id' => (int) ($row->user_id ?? 0), 'user_email' => $row->user_email ?? '', 'first_order' => $row->first_order_date ?? '', 'months_active' => $monthsActive, 'total_revenue' => $totalRevenue, 'avg_monthly_revenue' => (float) $avgMonthlyRevenue, 'estimated_clv' => (float) $estimatedClv, 'order_count' => (int) ($row->order_count ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();
        $oneMonthAgo = now()->subMonth()->toDateTimeString();
        $totalCustomers = DB::table('orders')->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$filters['from'], $filters['to']])->distinct('customer_id')->count('customer_id');
        $lastMonthRevenue = DB::table('orders')->where('payment_status', Order::STATUS_PAID)->whereRaw('created_at >= ?', [$oneMonthAgo])->whereRaw('created_at <= ?', [$now])->sum('grand_total');
        $avgMonthlyPerCustomer = $totalCustomers > 0 ? $lastMonthRevenue / $totalCustomers : 0;
        $totalProjectedClv = $avgMonthlyPerCustomer * $totalCustomers * 24;
        $averageClv = $avgMonthlyPerCustomer * 24;

        return ['total_customers' => (int) $totalCustomers, 'average_clv' => format_amount($averageClv), 'total_projected_clv' => format_amount($totalProjectedClv)];
    }
}
