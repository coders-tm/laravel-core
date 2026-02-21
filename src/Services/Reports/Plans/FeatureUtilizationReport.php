<?php

namespace Coderstm\Services\Reports\Plans;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class FeatureUtilizationReport extends AbstractReport
{
    protected array $columns = ['plan_id' => ['label' => 'Plan ID', 'type' => 'number'], 'plan_name' => ['label' => 'Plan Name', 'type' => 'text'], 'feature_name' => ['label' => 'Feature', 'type' => 'text'], 'feature_limit' => ['label' => 'Avg Limit', 'type' => 'number'], 'avg_usage' => ['label' => 'Avg Usage', 'type' => 'number'], 'utilization_rate' => ['label' => 'Utilization Rate', 'type' => 'percentage'], 'users_at_limit' => ['label' => 'Users at Limit', 'type' => 'number'], 'users_over_limit' => ['label' => 'Users Over Limit', 'type' => 'number']];

    public static function getType(): string
    {
        return 'feature-utilization';
    }

    public function getDescription(): string
    {
        return 'Analyze how plan features are being used by subscribers';
    }

    public function query(array $filters)
    {
        return DB::table('subscription_features')->join('subscriptions', 'subscriptions.id', '=', 'subscription_features.subscription_id')->join('plans', 'plans.id', '=', 'subscriptions.plan_id')->whereBetween('subscription_features.created_at', [$filters['from'], $filters['to']])->whereNull('subscriptions.canceled_at')->select(['plans.id as plan_id', 'plans.label as plan_name', 'subscription_features.slug as feature_slug', 'subscription_features.label as feature_name', DB::raw('COUNT(DISTINCT subscriptions.id) as subscription_count'), DB::raw('AVG(subscription_features.value) as avg_limit'), DB::raw('AVG(subscription_features.used) as avg_usage'), DB::raw('COUNT(DISTINCT CASE WHEN subscription_features.used >= subscription_features.value THEN subscriptions.id END) as users_at_limit'), DB::raw('COUNT(DISTINCT CASE WHEN subscription_features.used > subscription_features.value THEN subscriptions.id END) as users_over_limit')])->groupBy('plans.id', 'plans.label', 'subscription_features.slug', 'subscription_features.label')->orderBy('plans.label')->orderBy('subscription_features.label');
    }

    public function toRow($row): array
    {
        $avgLimit = (float) ($row->avg_limit ?? 0);
        $avgUsage = (float) ($row->avg_usage ?? 0);
        $utilizationRate = $avgLimit > 0 ? $avgUsage / $avgLimit * 100 : 0;

        return ['plan_id' => (int) ($row->plan_id ?? 0), 'plan_name' => $row->plan_name ?? '', 'feature_name' => $row->feature_name ?? $row->feature_slug ?? '', 'feature_limit' => (float) $avgLimit, 'avg_usage' => $avgUsage, 'utilization_rate' => (float) $utilizationRate, 'users_at_limit' => (int) ($row->users_at_limit ?? 0), 'users_over_limit' => (int) ($row->users_over_limit ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $summary = DB::table('subscription_features')->join('subscriptions', 'subscriptions.id', '=', 'subscription_features.subscription_id')->whereBetween('subscription_features.created_at', [$filters['from'], $filters['to']])->whereNull('subscriptions.canceled_at')->select([DB::raw('COUNT(DISTINCT subscriptions.plan_id) as total_plans'), DB::raw('COUNT(DISTINCT subscription_features.slug) as total_features_tracked'), DB::raw('AVG(subscription_features.used) as avg_usage_overall'), DB::raw('AVG(subscription_features.value) as avg_limit_overall')])->first();
        $avgUsage = (float) ($summary->avg_usage_overall ?? 0);
        $avgLimit = (float) ($summary->avg_limit_overall ?? 0);
        $overallUtilization = $avgLimit > 0 ? $avgUsage / $avgLimit * 100 : 0;

        return ['total_plans' => (int) ($summary->total_plans ?? 0), 'total_features_tracked' => (int) ($summary->total_features_tracked ?? 0), 'avg_usage' => $avgUsage, 'avg_limit' => $avgLimit, 'overall_utilization_rate' => (float) $overallUtilization];
    }
}
