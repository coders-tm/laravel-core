<?php

namespace Coderstm\Services\Reports\Subscriptions;

use Carbon\Carbon;
use Coderstm\Models\Subscription;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Subscription Lifecycle Report - Shows subscription states and transitions.
 */
class SubscriptionLifecycleReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'period' => ['label' => 'Period', 'type' => 'text'],
        'new_subscriptions' => ['label' => 'New Subscriptions', 'type' => 'number'],
        'active_subscriptions' => ['label' => 'Active Subscriptions', 'type' => 'number'],
        'trial_subscriptions' => ['label' => 'Trial Subscriptions', 'type' => 'number'],
        'canceled_subscriptions' => ['label' => 'Canceled Subscriptions', 'type' => 'number'],
        'expired_subscriptions' => ['label' => 'Expired Subscriptions', 'type' => 'number'],
        'frozen_subscriptions' => ['label' => 'Frozen Subscriptions', 'type' => 'number'],
        'grace_period' => ['label' => 'On Grace Period', 'type' => 'number'],
        'reactivations' => ['label' => 'Reactivations', 'type' => 'number'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'subscription-lifecycle';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Monitor subscription states and lifecycle transitions';
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $periods = $this->getDatePeriods();

        // Build period boundaries array
        $periodBoundaries = [];
        foreach ($periods as $index => $periodStart) {
            $periodEnd = $this->getPeriodEnd($periodStart);
            $periodBoundaries[] = [
                'start' => $periodStart->toDateTimeString(),
                'end' => $periodEnd->toDateTimeString(),
                'order' => $index,
            ];
        }

        $periodQuery = $this->buildPeriodBoundariesQuery($periodBoundaries);
        if ($periodQuery === null) {
            return $this->emptyQuery();
        }

        // Main query with all lifecycle counts as subqueries
        return DB::table(DB::raw("({$periodQuery->toSql()}) as periods"))
            ->mergeBindings($periodQuery)
            ->select([
                'periods.period_start',
                'periods.period_order',
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at BETWEEN periods.period_start AND periods.period_end
                    {$this->scopeClause()}
                ) as new_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND status = 'active'
                    AND canceled_at IS NULL
                    AND (expires_at IS NULL OR expires_at > periods.period_end)
                    {$this->scopeClause()}
                ) as active_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND trial_ends_at IS NOT NULL
                    AND trial_ends_at > periods.period_end
                    {$this->scopeClause()}
                ) as trial_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE canceled_at IS NOT NULL
                    AND canceled_at BETWEEN periods.period_start AND periods.period_end
                    {$this->scopeClause()}
                ) as canceled_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE expires_at IS NOT NULL
                    AND expires_at BETWEEN periods.period_start AND periods.period_end
                    AND canceled_at IS NOT NULL
                    {$this->scopeClause()}
                ) as expired_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND frozen_at IS NOT NULL
                    AND (release_at IS NULL OR release_at > periods.period_end)
                    {$this->scopeClause()}
                ) as frozen_subscriptions"),
                DB::raw("(
                    SELECT COUNT(*)
                    FROM subscriptions
                    WHERE created_at <= periods.period_end
                    AND canceled_at IS NOT NULL
                    AND expires_at IS NOT NULL
                    AND expires_at > periods.period_end
                    {$this->scopeClause()}
                ) as grace_period"),
                DB::raw('0 as reactivations'),
            ])
            ->orderBy('periods.period_order');
    }

    /**
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        $period = $this->formatPeriodLabel(Carbon::parse($row->period_start));

        return [
            'period' => $period,
            'new_subscriptions' => (int) ($row->new_subscriptions ?? 0),
            'active_subscriptions' => (int) ($row->active_subscriptions ?? 0),
            'trial_subscriptions' => (int) ($row->trial_subscriptions ?? 0),
            'canceled_subscriptions' => (int) ($row->canceled_subscriptions ?? 0),
            'expired_subscriptions' => (int) ($row->expired_subscriptions ?? 0),
            'frozen_subscriptions' => (int) ($row->frozen_subscriptions ?? 0),
            'grace_period' => (int) ($row->grace_period ?? 0),
            'reactivations' => (int) ($row->reactivations ?? 0),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $now = now()->toDateTimeString();

        $stats = Subscription::query()->toBase()
            ->selectRaw("
                COUNT(CASE WHEN status = 'active' AND canceled_at IS NULL THEN 1 END) as total_active,
                COUNT(CASE WHEN canceled_at IS NOT NULL THEN 1 END) as total_canceled
            ")
            ->first();

        return [
            'total_active' => (int) ($stats->total_active ?? 0),
            'total_canceled' => (int) ($stats->total_canceled ?? 0),
        ];
    }
}
