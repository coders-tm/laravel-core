<?php

namespace Coderstm\Services\Metrics;

use Carbon\Carbon;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionMetrics extends MetricsCalculator
{
    protected string $cachePrefix = 'subscription_metrics';

    public function getActiveCount(): int
    {
        return $this->remember('active_count', function () {
            return Subscription::query()->active()->count();
        });
    }

    public function getGracePeriodCount(): int
    {
        return $this->remember('grace_period', function () {
            return Subscription::query()->whereNotNull('canceled_at')->where('expires_at', '>', now())->count();
        });
    }

    public function getCancelledCount(): int
    {
        return $this->remember('cancelled_count', function () {
            $range = $this->getDateRange();

            return $this->cancelledBetween($range['start'], $range['end']);
        });
    }

    public function getTrialCount(): int
    {
        return $this->remember('trial_count', function () {
            return Subscription::query()->whereNotNull('trial_ends_at')->where('trial_ends_at', '>', now())->count();
        });
    }

    public function getChurnRate(): float
    {
        return $this->remember('churn_rate', function () {
            $range = $this->getDateRange();

            return $this->churnRateBetween($range['start'], $range['end']);
        });
    }

    public function getNewSubscriptions(): int
    {
        return $this->remember('new_subscriptions', function () {
            $range = $this->getDateRange();

            return $this->newSubscriptionsBetween($range['start'], $range['end']);
        });
    }

    public function getNewThisMonth(): int
    {
        return $this->remember('new_this_month', function () {
            return Subscription::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        });
    }

    public function getTrialConversionRate(): float
    {
        return $this->remember('trial_conversion', function () {
            $range = $this->getDateRange();

            return $this->trialConversionBetween($range['start'], $range['end']);
        });
    }

    public function getByPlan(): array
    {
        return $this->remember('by_plan', function () {
            return Subscription::query()->select('plans.label as plan_name', 'plans.id as plan_id', DB::raw('count(*) as count'))->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->active()->groupBy('plans.id', 'plans.label')->orderByDesc('count')->get()->toArray();
        });
    }

    public function getByInterval(): array
    {
        return $this->remember('by_interval', function () {
            return Subscription::query()->select('billing_interval', 'billing_interval_count', DB::raw('COUNT(*) as count'))->active()->groupBy('billing_interval', 'billing_interval_count')->orderBy('billing_interval')->orderBy('billing_interval_count')->get()->toArray();
        });
    }

    public function getByStatus(): array
    {
        return $this->remember('by_status', function () {
            return Subscription::query()->select('status', DB::raw('count(*) as count'))->groupBy('status')->get()->pluck('count', 'status')->toArray();
        });
    }

    public function getAverageLifetime(): float
    {
        return $this->remember('avg_lifetime', function () {
            $subscriptions = Subscription::query()->whereNotNull('canceled_at')->get(['created_at', 'canceled_at']);
            if ($subscriptions->isEmpty()) {
                return 0.0;
            }
            $totalDays = $subscriptions->sum(function ($sub) {
                return $sub->created_at->diffInDays($sub->canceled_at);
            });

            return round($totalDays / $subscriptions->count(), 2);
        });
    }

    public function getRetentionRate(): float
    {
        return $this->remember('retention_rate', function () {
            $range = $this->getDateRange();
            $startingSubscriptions = Subscription::query()->where('created_at', '<=', $range['start'])->count();
            if ($startingSubscriptions === 0) {
                return 0.0;
            }
            $retained = Subscription::query()->where('created_at', '<=', $range['start'])->where(function ($q) use ($range) {
                $q->whereNull('canceled_at')->orWhere('canceled_at', '>', $range['end']);
            })->count();

            return round($retained / $startingSubscriptions * 100, 2);
        });
    }

    public function getFrozenCount(): int
    {
        return $this->remember('frozen_count', function () {
            return Subscription::query()->whereNotNull('frozen_at')->where(function ($q) {
                $q->whereNull('release_at')->orWhere('release_at', '>', now());
            })->count();
        });
    }

    public function getPendingReleaseCount(): int
    {
        return $this->remember('pending_release', function () {
            return Subscription::query()->whereNotNull('frozen_at')->whereNotNull('release_at')->where('release_at', '<=', now()->addDays(7))->where('release_at', '>', now())->count();
        });
    }

    public function getContractCount(): int
    {
        return $this->remember('contract_count', function () {
            return Subscription::query()->whereNotNull('total_cycles')->where('total_cycles', '>', 0)->where(function ($q) {
                $q->whereNull('canceled_at')->orWhere('expires_at', '>', now());
            })->count();
        });
    }

    public function getContractsEndingSoon(): int
    {
        return $this->remember('contracts_ending_soon', function () {
            return Subscription::query()->whereNotNull('total_cycles')->where('total_cycles', '>', 0)->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(30)])->count();
        });
    }

    public function getRenewalForecast(): array
    {
        return $this->remember('renewal_forecast', function () {
            $next30Days = now()->addDays(30);
            $subscriptions = Subscription::query()->join('plans', 'plans.id', '=', 'subscriptions.plan_id')->select(DB::raw('DATE(subscriptions.expires_at) as renewal_date'), DB::raw('COUNT(*) as count'), DB::raw('SUM(
                        (CASE plans.interval
                            WHEN \'day\' THEN plans.price * 30
                            WHEN \'week\' THEN plans.price * 4.345
                            WHEN \'year\' THEN plans.price / 12
                            ELSE plans.price / COALESCE(plans.interval_count, 1)
                        END) * COALESCE(subscriptions.quantity, 1)
                    ) as expected_mrr'))->active()->whereNotNull('subscriptions.expires_at')->whereBetween('subscriptions.expires_at', [now(), $next30Days])->groupBy(DB::raw('DATE(subscriptions.expires_at)'))->orderBy('renewal_date')->get()->toArray();

            return ['renewals' => $subscriptions, 'total_count' => array_sum(array_column($subscriptions, 'count')), 'expected_mrr' => round(array_sum(array_column($subscriptions, 'expected_mrr')), 2)];
        });
    }

    public function getPlanChangeMetrics(): array
    {
        return $this->remember('plan_changes', function () {
            $range = $this->getDateRange();
            $scheduledDowngrades = Subscription::query()->where('is_downgrade', true)->whereNotNull('next_plan')->count();
            $upgrades = 0;
            $downgrades = 0;

            return ['scheduled_downgrades' => $scheduledDowngrades, 'upgrades' => $upgrades, 'downgrades' => $downgrades];
        });
    }

    public function getExpiringTodayCount(): int
    {
        return $this->remember('expiring_today', function () {
            return Subscription::query()->whereDate('expires_at', today())->count();
        });
    }

    public function getGrowthRate(): float
    {
        return $this->remember('growth_rate', function () {
            $currentMonth = Subscription::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
            $previousMonth = Subscription::query()->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
            if ($previousMonth === 0) {
                return $currentMonth > 0 ? 100.0 : 0.0;
            }

            return round(($currentMonth - $previousMonth) / $previousMonth * 100, 2);
        });
    }

    public function get(): array
    {
        $payload = ['active_count' => $this->getActiveCount(), 'grace_period_count' => $this->getGracePeriodCount(), 'cancelled_count' => $this->getCancelledCount(), 'trial_count' => $this->getTrialCount(), 'churn_rate' => $this->getChurnRate(), 'new_subscriptions' => $this->getNewSubscriptions(), 'new_this_month' => $this->getNewThisMonth(), 'trial_conversion_rate' => $this->getTrialConversionRate(), 'by_plan' => $this->getByPlan(), 'by_interval' => $this->getByInterval(), 'by_status' => $this->getByStatus(), 'average_lifetime_days' => $this->getAverageLifetime(), 'retention_rate' => $this->getRetentionRate(), 'growth_rate' => $this->getGrowthRate(), 'frozen_count' => $this->getFrozenCount(), 'pending_release_count' => $this->getPendingReleaseCount(), 'contract_count' => $this->getContractCount(), 'contracts_ending_soon' => $this->getContractsEndingSoon(), 'renewal_forecast' => $this->getRenewalForecast(), 'plan_changes' => $this->getPlanChangeMetrics(), 'expiring_today' => $this->getExpiringTodayCount(), 'metadata' => $this->getMetadata()];
        $periods = $this->getComparisonPeriods();

        return $this->withComparisons($payload, ['cancelled_count' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->cancelledBetween($start, $end), 'description' => __('Cancellations from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'new_subscriptions' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->newSubscriptionsBetween($start, $end), 'description' => __('New subscriptions from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'churn_rate' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->churnRateBetween($start, $end), 'type' => 'percentage', 'description' => __('Churn rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'trial_conversion_rate' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->trialConversionBetween($start, $end), 'type' => 'percentage', 'description' => __('Trial conversion from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])]]);
    }

    protected function cancelledBetween(Carbon $start, Carbon $end): int
    {
        return Subscription::query()->whereNotNull('canceled_at')->whereBetween('canceled_at', [$start, $end])->count();
    }

    protected function newSubscriptionsBetween(Carbon $start, Carbon $end): int
    {
        return Subscription::query()->whereBetween('created_at', [$start, $end])->count();
    }

    protected function churnRateBetween(Carbon $start, Carbon $end): float
    {
        $activeStart = Subscription::query()->where('created_at', '<=', $start)->where(function ($q) use ($start) {
            $q->whereNull('canceled_at')->orWhere('expires_at', '>', $start);
        })->count();
        if ($activeStart === 0) {
            return 0.0;
        }
        $churned = Subscription::query()->whereBetween('canceled_at', [$start, $end])->count();

        return round($churned / $activeStart * 100, 2);
    }

    protected function trialConversionBetween(Carbon $start, Carbon $end): float
    {
        $totalTrials = Subscription::query()->whereNotNull('trial_ends_at')->whereBetween('created_at', [$start, $end])->count();
        if ($totalTrials === 0) {
            return 0.0;
        }
        $converted = Subscription::query()->whereNotNull('trial_ends_at')->whereBetween('created_at', [$start, $end])->where(function ($q) {
            $q->whereNull('canceled_at')->orWhere('canceled_at', '>', DB::raw('trial_ends_at'));
        })->count();

        return round($converted / $totalTrials * 100, 2);
    }
}
