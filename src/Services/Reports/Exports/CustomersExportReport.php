<?php

namespace Coderstm\Services\Reports\Exports;

use Coderstm\Enum\AppStatus;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class CustomersExportReport extends AbstractReport
{
    protected array $columns = ['email' => ['label' => 'Customer Email', 'type' => 'text'], 'user_id' => ['label' => 'User ID', 'type' => 'number'], 'name' => ['label' => 'User Name', 'type' => 'text'], 'first_subscription_date' => ['label' => 'First Subscription Date', 'type' => 'text'], 'current_plan' => ['label' => 'Current Plan', 'type' => 'text'], 'subscription_status' => ['label' => 'Subscription Status', 'type' => 'text'], 'total_subscriptions' => ['label' => 'Total Subscriptions', 'type' => 'number'], 'total_orders' => ['label' => 'Total Orders', 'type' => 'number'], 'total_revenue' => ['label' => 'Total Revenue', 'type' => 'currency'], 'lifetime_value' => ['label' => 'Lifetime Value', 'type' => 'currency'], 'created_at' => ['label' => 'Created At', 'type' => 'text']];

    public static function getType(): string
    {
        return 'customers';
    }

    public function getDescription(): string
    {
        return 'Export customer profiles and statistics';
    }

    public function query(array $filters)
    {
        $firstSubQuery = DB::table('subscriptions as s1')->select(['s1.user_id', DB::raw('MIN(s1.created_at) as first_sub_date')])->groupBy('s1.user_id');
        $currentSubQuery = DB::table('subscriptions as s2')->select(['s2.user_id', 's2.status', 's2.plan_id as current_plan_id', DB::raw('MAX(s2.created_at) as current_sub_date')])->where('s2.status', AppStatus::ACTIVE->value)->whereNull('s2.canceled_at')->groupBy('s2.user_id', 's2.status', 's2.plan_id');
        $subCountQuery = DB::table('subscriptions as s3')->select(['s3.user_id', DB::raw('COUNT(*) as total_subs')])->groupBy('s3.user_id');
        $orderStatsQuery = DB::table('orders as o')->select(['o.customer_id', DB::raw('COUNT(*) as total_orders'), DB::raw('COALESCE(SUM(CASE WHEN o.payment_status = "paid" THEN o.grand_total ELSE 0 END), 0) as total_revenue')])->groupBy('o.customer_id');
        $mrrQuery = DB::table('subscriptions as s4')->join('plans', 's4.plan_id', '=', 'plans.id')->select(['s4.user_id', DB::raw('SUM(plans.price * COALESCE(s4.quantity, 1)) as mrr')])->whereNull('s4.canceled_at')->where(function ($q) {
            $q->whereNull('s4.expires_at')->orWhere('s4.expires_at', '>', now());
        })->groupBy('s4.user_id');

        return DB::table('users')->leftJoin(DB::raw("({$firstSubQuery->toSql()}) as first_sub"), 'users.id', '=', 'first_sub.user_id')->mergeBindings($firstSubQuery)->leftJoin(DB::raw("({$currentSubQuery->toSql()}) as current_sub"), 'users.id', '=', 'current_sub.user_id')->mergeBindings($currentSubQuery)->leftJoin('plans as current_plan', 'current_sub.current_plan_id', '=', 'current_plan.id')->leftJoin(DB::raw("({$subCountQuery->toSql()}) as sub_counts"), 'users.id', '=', 'sub_counts.user_id')->mergeBindings($subCountQuery)->leftJoin(DB::raw("({$orderStatsQuery->toSql()}) as order_stats"), 'users.id', '=', 'order_stats.customer_id')->mergeBindings($orderStatsQuery)->leftJoin(DB::raw("({$mrrQuery->toSql()}) as mrr_calc"), 'users.id', '=', 'mrr_calc.user_id')->mergeBindings($mrrQuery)->select(['users.id', 'users.email', 'users.name', 'users.created_at', 'first_sub.first_sub_date', 'current_sub.status as current_status', 'current_plan.label as current_plan_label', DB::raw('COALESCE(sub_counts.total_subs, 0) as total_subs'), DB::raw('COALESCE(order_stats.total_orders, 0) as total_orders'), DB::raw('COALESCE(order_stats.total_revenue, 0) as total_revenue'), DB::raw('COALESCE(mrr_calc.mrr, 0) as mrr')])->orderBy('users.created_at', 'desc');
    }

    public function toRow($row): array
    {
        $ltv = (float) ($row->mrr ?? 0) * 12;

        return ['email' => $row->email ?? '', 'user_id' => (int) $row->id, 'name' => $row->name ?? '', 'first_subscription_date' => $row->first_sub_date ?? '', 'current_plan' => $row->current_plan_label ?? 'No Active Plan', 'subscription_status' => $row->current_status ?? 'none', 'total_subscriptions' => (int) ($row->total_subs ?? 0), 'total_orders' => (int) ($row->total_orders ?? 0), 'total_revenue' => (float) ($row->total_revenue ?? 0), 'lifetime_value' => $ltv, 'created_at' => $row->created_at ?? ''];
    }

    public function summarize(array $filters): array
    {
        $query = $this->buildUserQuery();

        return ['total_customers' => (int) $query->count()];
    }
}
