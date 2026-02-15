<?php

namespace Coderstm\Services\Reports\Plans;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class PlanComparisonReport extends AbstractReport
{
    protected array $columns = ['plan_id' => ['label' => 'Plan ID', 'type' => 'number'], 'plan_name' => ['label' => 'Plan Name', 'type' => 'text'], 'price' => ['label' => 'Price', 'type' => 'currency'], 'interval' => ['label' => 'Interval', 'type' => 'text'], 'active_subscriptions' => ['label' => 'Active Subscriptions', 'type' => 'number'], 'total_signups' => ['label' => 'Total Signups', 'type' => 'number'], 'churn_count' => ['label' => 'Churned', 'type' => 'number'], 'churn_rate' => ['label' => 'Churn Rate', 'type' => 'percentage'], 'mrr' => ['label' => 'MRR', 'type' => 'currency'], 'revenue_share' => ['label' => 'Revenue Share', 'type' => 'percentage']];

    public static function getType(): string
    {
        return 'plan-comparison';
    }

    public function getDescription(): string
    {
        return 'Compare performance metrics across all subscription plans';
    }

    public function validate(array $input): array
    {
        $validated = validator($input, ['plan_id' => 'nullable|integer|exists:plans,id', 'status' => 'nullable|string|in:active,inactive,all'])->validate();

        return parent::validate($validated);
    }

    public function query(array $filters)
    {
        $to = $filters['to'];
        $from = $filters['from'];

        return DB::table('plans')->leftJoin('subscriptions', 'subscriptions.plan_id', '=', 'plans.id')->select(['plans.id as plan_id', 'plans.label as plan_name', 'plans.price', 'plans.interval', DB::raw("COUNT(DISTINCT CASE\n                    WHEN subscriptions.canceled_at IS NULL\n                    AND subscriptions.created_at <= '{$to}'\n                    THEN subscriptions.id\n                END) as active_subscriptions"), DB::raw("COUNT(DISTINCT CASE\n                    WHEN subscriptions.created_at >= '{$from}'\n                    AND subscriptions.created_at <= '{$to}'\n                    THEN subscriptions.id\n                END) as total_signups"), DB::raw("COUNT(DISTINCT CASE\n                    WHEN subscriptions.canceled_at IS NOT NULL\n                    AND subscriptions.canceled_at >= '{$from}'\n                    AND subscriptions.canceled_at <= '{$to}'\n                    THEN subscriptions.id\n                END) as churn_count")])->groupBy('plans.id', 'plans.label', 'plans.price', 'plans.interval')->orderBy('plans.label');
    }

    public function toRow($row): array
    {
        $activeSubscriptions = (int) ($row->active_subscriptions ?? 0);
        $churnCount = (int) ($row->churn_count ?? 0);
        $price = (float) ($row->price ?? 0);
        $churnRate = $activeSubscriptions > 0 ? $churnCount / ($activeSubscriptions + $churnCount) * 100 : 0;
        $mrr = $activeSubscriptions * $price;

        return ['plan_id' => (int) $row->plan_id, 'plan_name' => $row->plan_name ?? '', 'price' => $price, 'interval' => $row->interval ?? 'month', 'active_subscriptions' => $activeSubscriptions, 'total_signups' => (int) ($row->total_signups ?? 0), 'churn_count' => $churnCount, 'churn_rate' => (float) $churnRate, 'mrr' => (float) $mrr, 'revenue_share' => (float) ($row->revenue_share ?? 0)];
    }

    public function stream(array $filters, callable $consume): void
    {
        $query = $this->query($filters);
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $query->limit($filters['limit']);
        }
        $rows = $query->get();
        $totalMrr = $rows->sum(function ($row) {
            return (int) $row->active_subscriptions * (float) ($row->price ?? 0);
        });
        foreach ($rows as $row) {
            $mrr = (int) $row->active_subscriptions * (float) ($row->price ?? 0);
            $row->revenue_share = $totalMrr > 0 ? $mrr / $totalMrr * 100 : 0;
            $consume($this->toRow($row));
        }
    }

    public function summarize(array $filters): array
    {
        $summary = DB::table('plans')->leftJoin('subscriptions', function ($join) use ($filters) {
            $join->on('subscriptions.plan_id', '=', 'plans.id')->whereNull('subscriptions.canceled_at')->where('subscriptions.created_at', '<=', $filters['to']);
        })->select([DB::raw('COUNT(DISTINCT plans.id) as total_plans'), DB::raw('COUNT(DISTINCT subscriptions.id) as total_active_subscriptions'), DB::raw('COALESCE(SUM(plans.price), 0) as total_mrr_base')])->first();
        $totalMrr = DB::table('plans')->leftJoin('subscriptions', function ($join) use ($filters) {
            $join->on('subscriptions.plan_id', '=', 'plans.id')->whereNull('subscriptions.canceled_at')->where('subscriptions.created_at', '<=', $filters['to']);
        })->select(['plans.price', DB::raw('COUNT(subscriptions.id) as sub_count')])->groupBy('plans.id', 'plans.price')->get()->sum(fn ($row) => (float) $row->price * (int) $row->sub_count);

        return ['total_plans' => (int) ($summary->total_plans ?? 0), 'total_active_subscriptions' => (int) ($summary->total_active_subscriptions ?? 0), 'total_mrr' => format_amount($totalMrr)];
    }
}
