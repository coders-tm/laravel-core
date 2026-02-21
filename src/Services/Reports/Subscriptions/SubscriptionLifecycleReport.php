<?php

namespace Coderstm\Services\Reports\Subscriptions;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class SubscriptionLifecycleReport extends AbstractReport
{
    protected array $columns = ['period' => ['label' => 'Period', 'type' => 'text'], 'new_subscriptions' => ['label' => 'New Subscriptions', 'type' => 'number'], 'active_subscriptions' => ['label' => 'Active Subscriptions', 'type' => 'number'], 'trial_subscriptions' => ['label' => 'Trial Subscriptions', 'type' => 'number'], 'canceled_subscriptions' => ['label' => 'Canceled Subscriptions', 'type' => 'number'], 'expired_subscriptions' => ['label' => 'Expired Subscriptions', 'type' => 'number'], 'frozen_subscriptions' => ['label' => 'Frozen Subscriptions', 'type' => 'number'], 'grace_period' => ['label' => 'On Grace Period', 'type' => 'number'], 'reactivations' => ['label' => 'Reactivations', 'type' => 'number']];

    public static function getType(): string
    {
        return 'subscription-lifecycle';
    }

    public function getDescription(): string
    {
        return 'Monitor subscription states and lifecycle transitions';
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

        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))->mergeBindings($periodQuery)->select(['periods.period_start', 'periods.period_order', DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at BETWEEN periods.period_start AND periods.period_end
                ) as new_subscriptions'), DB::raw("(\n                    SELECT COUNT(*)\n                    FROM subscriptions\n                    WHERE created_at <= periods.period_end\n                    AND status = 'active'\n                    AND canceled_at IS NULL\n                    AND (expires_at IS NULL OR expires_at > periods.period_end)\n                ) as active_subscriptions"), DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND trial_ends_at IS NOT NULL
                    AND trial_ends_at > periods.period_end
                ) as trial_subscriptions'), DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE canceled_at IS NOT NULL
                    AND canceled_at BETWEEN periods.period_start AND periods.period_end
                ) as canceled_subscriptions'), DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE expires_at IS NOT NULL
                    AND expires_at BETWEEN periods.period_start AND periods.period_end
                    AND canceled_at IS NOT NULL
                ) as expired_subscriptions'), DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND frozen_at IS NOT NULL
                    AND (release_at IS NULL OR release_at > periods.period_end)
                ) as frozen_subscriptions'), DB::raw('(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND canceled_at IS NOT NULL
                    AND expires_at IS NOT NULL
                    AND expires_at > periods.period_end
                ) as grace_period'), DB::raw('0 as reactivations')])->orderBy('periods.period_order');
    }

    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(\Carbon\Carbon::parse($row->period_start));

        return ['period' => $period, 'new_subscriptions' => (int) ($row->new_subscriptions ?? 0), 'active_subscriptions' => (int) ($row->active_subscriptions ?? 0), 'trial_subscriptions' => (int) ($row->trial_subscriptions ?? 0), 'canceled_subscriptions' => (int) ($row->canceled_subscriptions ?? 0), 'expired_subscriptions' => (int) ($row->expired_subscriptions ?? 0), 'frozen_subscriptions' => (int) ($row->frozen_subscriptions ?? 0), 'grace_period' => (int) ($row->grace_period ?? 0), 'reactivations' => (int) ($row->reactivations ?? 0)];
    }

    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();
        $stats = DB::table('subscriptions')->selectRaw("\n                COUNT(CASE WHEN status = 'active' AND canceled_at IS NULL THEN 1 END) as total_active,\n                COUNT(CASE WHEN canceled_at IS NOT NULL THEN 1 END) as total_canceled\n            ")->first();

        return ['total_active' => (int) ($stats->total_active ?? 0), 'total_canceled' => (int) ($stats->total_canceled ?? 0)];
    }
}
