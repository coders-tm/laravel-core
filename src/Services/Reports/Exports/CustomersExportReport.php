<?php

namespace Coderstm\Services\Reports\Exports;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Models\Subscription;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Customers Export Report
 *
 * Exports customer profiles with aggregated subscription and order metrics via subqueries.
 * LTV estimated as MRR × 12 months. Returns all users via LEFT JOIN strategy.
 */
class CustomersExportReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'email' => ['label' => 'Customer Email', 'type' => 'text'],
        'user_id' => ['label' => 'User ID', 'type' => 'number'],
        'name' => ['label' => 'User Name', 'type' => 'text'],
        'first_subscription_date' => ['label' => 'First Subscription Date', 'type' => 'text'],
        'current_plan' => ['label' => 'Current Plan', 'type' => 'text'],
        'subscription_status' => ['label' => 'Subscription Status', 'type' => 'text'],
        'total_subscriptions' => ['label' => 'Total Subscriptions', 'type' => 'number'],
        'total_orders' => ['label' => 'Total Orders', 'type' => 'number'],
        'total_revenue' => ['label' => 'Total Revenue', 'type' => 'currency'],
        'lifetime_value' => ['label' => 'Lifetime Value', 'type' => 'currency'],
        'created_at' => ['label' => 'Created At', 'type' => 'text'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'customers';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Export customer profiles and statistics';
    }

    /**
     * Build the base query with all customer metrics in single query.
     *
     * Uses subqueries and JOINs to aggregate all stats without N+1 queries.
     *
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        // Subquery for first subscription
        $firstSubQuery = Subscription::query()->toBase()
            ->select([
                'subscriptions.user_id',
                DB::raw('MIN(subscriptions.created_at) as first_sub_date'),
            ])
            ->groupBy('subscriptions.user_id');

        // Subquery for current active subscription
        $currentSubQuery = Subscription::query()->toBase()
            ->select([
                'subscriptions.user_id',
                'subscriptions.status',
                'subscriptions.plan_id as current_plan_id',
                DB::raw('MAX(subscriptions.created_at) as current_sub_date'),
            ])
            ->where('subscriptions.status', AppStatus::ACTIVE->value)
            ->whereNull('subscriptions.canceled_at')
            ->groupBy('subscriptions.user_id', 'subscriptions.status', 'subscriptions.plan_id');

        // Subquery for subscription counts
        $subCountQuery = Subscription::query()->toBase()
            ->select([
                'subscriptions.user_id',
                DB::raw('COUNT(*) as total_subs'),
            ])
            ->groupBy('subscriptions.user_id');

        // Subquery for order stats
        $orderStatsQuery = Coderstm::$orderModel::query()->toBase()
            ->select([
                'orders.customer_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('COALESCE(SUM(CASE WHEN orders.payment_status = "paid" THEN orders.grand_total ELSE 0 END), 0) as total_revenue'),
            ])
            ->groupBy('orders.customer_id');

        // Subquery for MRR/LTV calculation (active subscriptions with plan prices)
        $mrrQuery = Subscription::query()->toBase()
            ->join('plans', 'subscriptions.plan_id', '=', 'plans.id')
            ->select([
                'subscriptions.user_id',
                DB::raw('SUM(plans.price * COALESCE(subscriptions.quantity, 1)) as mrr'),
            ])
            ->whereNull('subscriptions.canceled_at')
            ->where(function ($q) {
                $q->whereNull('subscriptions.expires_at')
                    ->orWhere('subscriptions.expires_at', '>', now());
            })
            ->groupBy('subscriptions.user_id');

        return Coderstm::$userModel::query()->toBase()
            ->leftJoinSub($firstSubQuery, 'first_sub', 'users.id', '=', 'first_sub.user_id')
            ->leftJoinSub($currentSubQuery, 'current_sub', 'users.id', '=', 'current_sub.user_id')
            ->leftJoin('plans as current_plan', 'current_sub.current_plan_id', '=', 'current_plan.id')
            ->leftJoinSub($subCountQuery, 'sub_counts', 'users.id', '=', 'sub_counts.user_id')
            ->leftJoinSub($orderStatsQuery, 'order_stats', 'users.id', '=', 'order_stats.customer_id')
            ->leftJoinSub($mrrQuery, 'mrr_calc', 'users.id', '=', 'mrr_calc.user_id')
            ->select([
                'users.id',
                'users.email',
                'users.name',
                'users.created_at',
                'first_sub.first_sub_date',
                'current_sub.status as current_status',
                'current_plan.label as current_plan_label',
                DB::raw('COALESCE(sub_counts.total_subs, 0) as total_subs'),
                DB::raw('COALESCE(order_stats.total_orders, 0) as total_orders'),
                DB::raw('COALESCE(order_stats.total_revenue, 0) as total_revenue'),
                DB::raw('COALESCE(mrr_calc.mrr, 0) as mrr'),
            ])
            ->orderBy('users.created_at', 'desc');
    }

    /**
     * Transform row to array with raw values.
     *
     * No additional queries - all data already fetched by main query.
     *
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        // LTV = MRR × 12 months (simple estimate)
        $ltv = (float) ($row->mrr ?? 0) * 12;

        return [
            'email' => $row->email ?? '',
            'user_id' => (int) $row->id,
            'name' => $row->name ?? '',
            'first_subscription_date' => $row->first_sub_date ?? '',
            'current_plan' => $row->current_plan_label ?? 'No Active Plan',
            'subscription_status' => $row->current_status ?? 'none',
            'total_subscriptions' => (int) ($row->total_subs ?? 0),
            'total_orders' => (int) ($row->total_orders ?? 0),
            'total_revenue' => (float) ($row->total_revenue ?? 0),
            'lifetime_value' => $ltv,
            'created_at' => $row->created_at ?? '',
        ];
    }

    /**
     * Calculate summary statistics.
     *
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $query = $this->buildUserQuery();

        return [
            'total_customers' => (int) $query->count(),
        ];
    }
}
