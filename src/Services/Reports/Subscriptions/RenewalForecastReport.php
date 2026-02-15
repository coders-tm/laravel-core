<?php

namespace Coderstm\Services\Reports\Subscriptions;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class RenewalForecastReport extends AbstractReport
{
    protected array $columns = ['renewal_date' => ['label' => 'Renewal Date', 'type' => 'text'], 'renewals_count' => ['label' => 'Renewals Count', 'type' => 'number'], 'expected_revenue' => ['label' => 'Expected Revenue', 'type' => 'currency'], 'expected_mrr' => ['label' => 'Expected MRR', 'type' => 'currency'], 'at_risk_count' => ['label' => 'At Risk Count', 'type' => 'number'], 'at_risk_revenue' => ['label' => 'At Risk Revenue', 'type' => 'currency']];

    public static function getType(): string
    {
        return 'renewal-forecast';
    }

    public function getDescription(): string
    {
        return 'Forecast upcoming renewals and expected revenue';
    }

    public function query(array $filters)
    {
        $now = now()->toDateTimeString();
        $endDate = $filters['to'] ?? now()->addDays(30)->toDateTimeString();
        $dateExtract = $this->dbDateFormat('subscriptions.expires_at', 'Y-m-d');

        return DB::table('subscriptions')->leftJoin('plans', 'subscriptions.plan_id', '=', 'plans.id')->whereNotNull('subscriptions.expires_at')->where('subscriptions.expires_at', '>=', $now)->where('subscriptions.expires_at', '<=', $endDate)->whereNull('subscriptions.canceled_at')->select([DB::raw("{$dateExtract} as renewal_date"), DB::raw('COUNT(*) as renewals_count'), DB::raw('COALESCE(SUM(plans.price * COALESCE(subscriptions.quantity, 1)), 0) as expected_revenue'), DB::raw("COALESCE(SUM(\n                    CASE plans.interval\n                        WHEN 'year' THEN plans.price / 12\n                        WHEN 'week' THEN plans.price * 4\n                        WHEN 'day' THEN plans.price * 30\n                        ELSE plans.price\n                    END * COALESCE(subscriptions.quantity, 1)\n                ), 0) as expected_mrr"), DB::raw("COUNT(CASE WHEN subscriptions.status IN ('past_due', 'incomplete') THEN 1 END) as at_risk_count"), DB::raw("COALESCE(SUM(\n                    CASE WHEN subscriptions.status IN ('past_due', 'incomplete')\n                    THEN plans.price * COALESCE(subscriptions.quantity, 1)\n                    ELSE 0 END\n                ), 0) as at_risk_revenue")])->groupBy(DB::raw($dateExtract))->orderBy('renewal_date');
    }

    public function toRow($row): array
    {
        return ['renewal_date' => $row->renewal_date ?? '', 'renewals_count' => (int) ($row->renewals_count ?? 0), 'expected_revenue' => (float) (float) ($row->expected_revenue ?? 0), 'expected_mrr' => (float) (float) ($row->expected_mrr ?? 0), 'at_risk_count' => (int) ($row->at_risk_count ?? 0), 'at_risk_revenue' => (float) (float) ($row->at_risk_revenue ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();
        $next30Days = now()->addDays(30)->toDateTimeString();
        $totalRenewals = DB::table('subscriptions')->whereNotNull('expires_at')->whereRaw('expires_at >= ?', [$now])->whereRaw('expires_at <= ?', [$next30Days])->whereNull('canceled_at')->count();

        return ['total_renewals_next_30_days' => $totalRenewals];
    }
}
