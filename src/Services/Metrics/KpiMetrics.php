<?php

namespace Coderstm\Services\Metrics;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Contracts\SubscriptionStatus;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\DB;

class KpiMetrics extends MetricsCalculator
{
    protected string $cachePrefix = 'kpi_metrics';

    protected int $cacheTTL = 300;

    public function get(): array
    {
        $current = $this->getCurrentPeriod();
        $previous = $this->getPreviousPeriod();
        $periods = $this->getComparisonPeriods();

        return ['mrr' => $this->getMrrComparison($current, $previous), 'churn' => $this->getChurnComparison($current, $previous), 'ltv' => $this->getLtvComparison($current, $previous), 'arpu' => $this->getArpuComparison($current, $previous), 'cac' => $this->getCacComparison($current, $previous), 'active_users' => $this->getActiveUsersComparison($current, $previous), 'order_count' => $this->getOrderCountComparison($current, $previous), 'total_revenue' => $this->getGrossRevenueComparison($current, $previous), 'gross_revenue' => $this->getGrossRevenueComparison($current, $previous), 'net_revenue' => $this->getNetRevenueComparison($current, $previous), 'aov' => $this->getAovComparison($current, $previous), 'refund_rate' => $this->getRefundRateComparison($current, $previous), 'failed_payment_rate' => $this->getFailedPaymentRateComparison($current, $previous), 'repeat_rate' => $this->getRepeatRateComparison($current, $previous), 'metadata' => $this->getPeriodMetadata($current, $previous), 'new_customers' => $this->getNewCustomersComparison($periods), 'new_subscriptions' => $this->getNewSubscriptionsComparison($periods)];
    }

    public function only(array $keys): array
    {
        $current = $this->getCurrentPeriod();
        $previous = $this->getPreviousPeriod();
        $periods = $this->getComparisonPeriods();
        $availableKpis = ['mrr' => fn () => $this->getMrrComparison($current, $previous), 'churn' => fn () => $this->getChurnComparison($current, $previous), 'ltv' => fn () => $this->getLtvComparison($current, $previous), 'arpu' => fn () => $this->getArpuComparison($current, $previous), 'cac' => fn () => $this->getCacComparison($current, $previous), 'active_users' => fn () => $this->getActiveUsersComparison($current, $previous), 'order_count' => fn () => $this->getOrderCountComparison($current, $previous), 'total_revenue' => fn () => $this->getGrossRevenueComparison($current, $previous), 'gross_revenue' => fn () => $this->getGrossRevenueComparison($current, $previous), 'net_revenue' => fn () => $this->getNetRevenueComparison($current, $previous), 'aov' => fn () => $this->getAovComparison($current, $previous), 'refund_rate' => fn () => $this->getRefundRateComparison($current, $previous), 'failed_payment_rate' => fn () => $this->getFailedPaymentRateComparison($current, $previous), 'repeat_rate' => fn () => $this->getRepeatRateComparison($current, $previous), 'new_customers' => fn () => $this->getNewCustomersComparison($periods), 'new_subscriptions' => fn () => $this->getNewSubscriptionsComparison($periods)];
        $result = [];
        foreach ($keys as $key) {
            if (isset($availableKpis[$key])) {
                $result[$key] = $availableKpis[$key]();
            }
        }
        $result['metadata'] = $this->getPeriodMetadata($current, $previous);

        return $result;
    }

    protected function getComparisonPeriods(): array
    {
        $current = $this->getCurrentPeriod();
        $previous = $this->getPreviousPeriod();

        return ['current' => $current, 'previous' => $previous];
    }

    protected function getNewCustomersComparison(array $periods): array
    {
        $current = $periods['current'];
        $previous = $periods['previous'];
        $currentCount = Coderstm::$userModel::query()->whereBetween('created_at', [$current['start'], $current['end']])->count();
        $previousCount = Coderstm::$userModel::query()->whereBetween('created_at', [$previous['start'], $previous['end']])->count();
        $description = __('New customers from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentCount, $previousCount, 'count', ['description' => $description]);
    }

    protected function getNewSubscriptionsComparison(array $periods): array
    {
        $current = $periods['current'];
        $previous = $periods['previous'];
        $currentCount = Subscription::query()->whereBetween('created_at', [$current['start'], $current['end']])->count();
        $previousCount = Subscription::query()->whereBetween('created_at', [$previous['start'], $previous['end']])->count();
        $description = __('New subscriptions from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentCount, $previousCount, 'count', ['description' => $description]);
    }

    protected function getCurrentPeriod(): array
    {
        $range = $this->getDateRange();

        return ['start' => $range['start'], 'end' => $range['end']];
    }

    protected function getPreviousPeriod(): array
    {
        $current = $this->getCurrentPeriod();
        $diff = $current['start']->diffInDays($current['end']);

        return ['start' => $current['start']->copy()->subDays($diff + 1), 'end' => $current['start']->copy()->subDay()];
    }

    protected function getMrrComparison(array $current, array $previous): array
    {
        $currentMrr = $this->calculateMrr($current['end']);
        $previousMrr = $this->calculateMrr($previous['end']);
        $description = __('MRR from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentMrr, $previousMrr, 'currency', ['description' => $description, 'by_plan' => $this->getMrrByPlan($current['end']), 'by_interval' => $this->getMrrByInterval($current['end'])]);
    }

    protected function calculateMrr(Carbon $date): float
    {
        return DB::table('subscriptions')->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->where('subscriptions.status', SubscriptionStatus::ACTIVE)->where('subscriptions.created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('subscriptions.canceled_at')->orWhere('subscriptions.expires_at', '>', $date);
        })->sum(DB::raw("\n                CASE plans.interval\n                    WHEN 'day' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 30\n                    WHEN 'week' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 4.345\n                    WHEN 'month' THEN (plans.price / COALESCE(plans.interval_count, 1))\n                    WHEN 'year' THEN (plans.price / COALESCE(plans.interval_count, 1)) / 12\n                    ELSE 0\n                END * COALESCE(subscriptions.quantity, 1)\n            ")) ?? 0.0;
    }

    protected function getMrrByPlan(Carbon $date): array
    {
        return DB::table('subscriptions')->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->select('plans.label as plan', DB::raw("\n                SUM(CASE plans.interval\n                    WHEN 'day' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 30\n                    WHEN 'week' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 4.345\n                    WHEN 'month' THEN (plans.price / COALESCE(plans.interval_count, 1))\n                    WHEN 'year' THEN (plans.price / COALESCE(plans.interval_count, 1)) / 12\n                    ELSE 0\n                END * COALESCE(subscriptions.quantity, 1)) as mrr\n            "))->where('subscriptions.status', SubscriptionStatus::ACTIVE)->where('subscriptions.created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('subscriptions.canceled_at')->orWhere('subscriptions.expires_at', '>', $date);
        })->groupBy('plans.id', 'plans.label')->get()->map(fn ($item) => ['plan' => $item->plan, 'mrr' => round($item->mrr, 2)])->toArray();
    }

    protected function getMrrByInterval(Carbon $date): array
    {
        return DB::table('subscriptions')->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->select('plans.interval', 'plans.interval_count', DB::raw("SUM(CASE plans.interval\n                    WHEN 'day' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 30\n                    WHEN 'week' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 4.345\n                    WHEN 'month' THEN (plans.price / COALESCE(plans.interval_count, 1))\n                    WHEN 'year' THEN (plans.price / COALESCE(plans.interval_count, 1)) / 12\n                    ELSE 0\n                END * COALESCE(subscriptions.quantity, 1)) as mrr"))->where('subscriptions.status', SubscriptionStatus::ACTIVE)->where('subscriptions.created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('subscriptions.canceled_at')->orWhere('subscriptions.expires_at', '>', $date);
        })->groupBy('plans.interval', 'plans.interval_count')->get()->map(fn ($item) => ['interval' => $item->interval, 'interval_count' => $item->interval_count, 'mrr' => round($item->mrr, 2)])->toArray();
    }

    protected function getChurnComparison(array $current, array $previous): array
    {
        $currentChurn = $this->calculateChurnRate($current['start'], $current['end']);
        $previousChurn = $this->calculateChurnRate($previous['start'], $previous['end']);
        $description = __('Churn rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentChurn, $previousChurn, 'percentage', ['description' => $description, 'logo_churn' => $this->calculateLogoChurn($current['start'], $current['end']), 'revenue_churn' => $this->calculateRevenueChurn($current['start'], $current['end'])]);
    }

    protected function calculateChurnRate(Carbon $start, Carbon $end): float
    {
        $activeStart = Subscription::query()->where('created_at', '<=', $start)->where(function ($q) use ($start) {
            $q->whereNull('canceled_at')->orWhere('expires_at', '>', $start);
        })->count();
        if ($activeStart === 0) {
            return 0.0;
        }
        $churned = Subscription::query()->whereBetween('canceled_at', [$start, $end])->count();

        return round($churned / $activeStart, 4);
    }

    protected function calculateLogoChurn(Carbon $start, Carbon $end): array
    {
        $activeStart = Subscription::query()->where('created_at', '<=', $start)->where(function ($q) use ($start) {
            $q->whereNull('canceled_at')->orWhere('expires_at', '>', $start);
        })->distinct('user_id')->count('user_id');
        $churned = Subscription::query()->whereBetween('canceled_at', [$start, $end])->distinct('user_id')->count('user_id');
        $rate = $activeStart > 0 ? round($churned / $activeStart, 4) : 0.0;

        return ['churned_customers' => $churned, 'rate' => $rate];
    }

    protected function calculateRevenueChurn(Carbon $start, Carbon $end): array
    {
        $mrrStart = $this->calculateMrr($start);
        $lostMrr = DB::table('subscriptions')->join('plans', 'subscriptions.plan_id', '=', 'plans.id')->whereBetween('subscriptions.canceled_at', [$start, $end])->sum(DB::raw("\n                CASE plans.interval\n                    WHEN 'day' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 30\n                    WHEN 'week' THEN (plans.price / COALESCE(plans.interval_count, 1)) * 4.345\n                    WHEN 'month' THEN (plans.price / COALESCE(plans.interval_count, 1))\n                    WHEN 'year' THEN (plans.price / COALESCE(plans.interval_count, 1)) / 12\n                    ELSE 0\n                END * COALESCE(subscriptions.quantity, 1)\n            ")) ?? 0.0;
        $rate = $mrrStart > 0 ? round($lostMrr / $mrrStart, 4) : 0.0;

        return ['lost_mrr' => round($lostMrr, 2), 'rate' => $rate];
    }

    protected function getLtvComparison(array $current, array $previous): array
    {
        $currentLtv = $this->calculateLtv($current['start'], $current['end']);
        $previousLtv = $this->calculateLtv($previous['start'], $previous['end']);
        $description = __('Customer LTV from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentLtv, $previousLtv, 'currency', ['description' => $description]);
    }

    protected function calculateLtv(Carbon $start, Carbon $end): float
    {
        $arpu = $this->calculateArpu($start, $end);
        $churnRate = $this->calculateChurnRate($start, $end);
        if ($churnRate === 0.0) {
            return 0.0;
        }

        return round($arpu / $churnRate, 2);
    }

    protected function getArpuComparison(array $current, array $previous): array
    {
        $currentArpu = $this->calculateArpu($current['start'], $current['end']);
        $previousArpu = $this->calculateArpu($previous['start'], $previous['end']);
        $description = __('Average revenue per user from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentArpu, $previousArpu, 'currency', ['description' => $description]);
    }

    protected function calculateArpu(Carbon $start, Carbon $end): float
    {
        $totalRevenue = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->sum('grand_total') ?? 0.0;
        $userModel = Coderstm::$userModel;
        $totalUsers = $userModel::query()->where('created_at', '<=', $end)->count();

        return $totalUsers > 0 ? round($totalRevenue / $totalUsers, 2) : 0.0;
    }

    protected function getCacComparison(array $current, array $previous): array
    {
        $currentCac = 0.0;
        $previousCac = 0.0;

        return $this->formatComparison($currentCac, $previousCac, 'currency', ['note' => 'CAC calculation requires marketing spend data integration']);
    }

    protected function getActiveUsersComparison(array $current, array $previous): array
    {
        $currentActive = $this->calculateActiveUsers($current['end']);
        $previousActive = $this->calculateActiveUsers($previous['end']);
        $description = __('Active users from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentActive, $previousActive, 'count', ['description' => $description, 'with_subscription' => $this->calculateActiveSubscribers($current['end']), 'with_orders' => $this->calculateActiveOrderers($current['start'], $current['end'])]);
    }

    protected function calculateActiveUsers(Carbon $date): int
    {
        $userModel = Coderstm::$userModel;
        $withSubscription = Subscription::query()->where('status', SubscriptionStatus::ACTIVE)->where('created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('canceled_at')->orWhere('expires_at', '>', $date);
        })->distinct('user_id')->pluck('user_id');
        $withOrders = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$date->copy()->subDays(30), $date])->distinct('customer_id')->pluck('customer_id');

        return $withSubscription->merge($withOrders)->unique()->count();
    }

    protected function calculateActiveSubscribers(Carbon $date): int
    {
        return Subscription::query()->where('status', SubscriptionStatus::ACTIVE)->where('created_at', '<=', $date)->where(function ($q) use ($date) {
            $q->whereNull('canceled_at')->orWhere('expires_at', '>', $date);
        })->distinct('user_id')->count('user_id');
    }

    protected function calculateActiveOrderers(Carbon $start, Carbon $end): int
    {
        return Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id');
    }

    protected function getOrdersKpis(array $current, array $previous): array
    {
        return ['count' => $this->getOrderCountComparison($current, $previous), 'total_revenue' => $this->getGrossRevenueComparison($current, $previous), 'gross_revenue' => $this->getGrossRevenueComparison($current, $previous), 'net_revenue' => $this->getNetRevenueComparison($current, $previous), 'aov' => $this->getAovComparison($current, $previous), 'refund_rate' => $this->getRefundRateComparison($current, $previous), 'failed_payment_rate' => $this->getFailedPaymentRateComparison($current, $previous), 'repeat_rate' => $this->getRepeatRateComparison($current, $previous)];
    }

    protected function getOrderCountComparison(array $current, array $previous): array
    {
        $currentCount = Order::query()->whereBetween('created_at', [$current['start'], $current['end']])->count();
        $previousCount = Order::query()->whereBetween('created_at', [$previous['start'], $previous['end']])->count();
        $description = __('Orders from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentCount, $previousCount, 'count', ['description' => $description]);
    }

    protected function getGrossRevenueComparison(array $current, array $previous): array
    {
        $currentRevenue = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$current['start'], $current['end']])->sum(DB::raw('sub_total + tax_total + shipping_total')) ?? 0.0;
        $previousRevenue = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$previous['start'], $previous['end']])->sum(DB::raw('sub_total + tax_total + shipping_total')) ?? 0.0;
        $description = __('Revenue from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentRevenue, $previousRevenue, 'currency', ['description' => $description]);
    }

    protected function getNetRevenueComparison(array $current, array $previous): array
    {
        $currentRevenue = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$current['start'], $current['end']])->sum('grand_total') ?? 0.0;
        $currentRefunds = Order::query()->whereIn('payment_status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])->whereBetween('created_at', [$current['start'], $current['end']])->sum('refund_total') ?? 0.0;
        $previousRevenue = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$previous['start'], $previous['end']])->sum('grand_total') ?? 0.0;
        $previousRefunds = Order::query()->whereIn('payment_status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])->whereBetween('created_at', [$previous['start'], $previous['end']])->sum('refund_total') ?? 0.0;
        $description = __('Net revenue from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentRevenue - $currentRefunds, $previousRevenue - $previousRefunds, 'currency', ['description' => $description]);
    }

    protected function getAovComparison(array $current, array $previous): array
    {
        $currentAov = $this->calculateAov($current['start'], $current['end']);
        $previousAov = $this->calculateAov($previous['start'], $previous['end']);
        $description = __('Average order value from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentAov, $previousAov, 'currency', ['description' => $description]);
    }

    protected function calculateAov(Carbon $start, Carbon $end): float
    {
        $total = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->sum('grand_total') ?? 0.0;
        $count = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->count();

        return $count > 0 ? round($total / $count, 2) : 0.0;
    }

    protected function getRefundRateComparison(array $current, array $previous): array
    {
        $currentRate = $this->calculateRefundRate($current['start'], $current['end']);
        $previousRate = $this->calculateRefundRate($previous['start'], $previous['end']);
        $description = __('Refund rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentRate, $previousRate, 'percentage', ['description' => $description]);
    }

    protected function calculateRefundRate(Carbon $start, Carbon $end): float
    {
        $total = Order::query()->whereBetween('created_at', [$start, $end])->count();
        if ($total === 0) {
            return 0.0;
        }
        $refunded = Order::query()->whereIn('payment_status', [Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])->whereBetween('created_at', [$start, $end])->count();

        return round($refunded / $total, 4);
    }

    protected function getFailedPaymentRateComparison(array $current, array $previous): array
    {
        $currentRate = $this->calculateFailedPaymentRate($current['start'], $current['end']);
        $previousRate = $this->calculateFailedPaymentRate($previous['start'], $previous['end']);
        $description = __('Failed payment rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentRate, $previousRate, 'percentage', ['description' => $description]);
    }

    protected function calculateFailedPaymentRate(Carbon $start, Carbon $end): float
    {
        $total = Order::query()->whereBetween('created_at', [$start, $end])->count();
        if ($total === 0) {
            return 0.0;
        }
        $failed = Order::query()->where('payment_status', Order::STATUS_PAYMENT_FAILED)->whereBetween('created_at', [$start, $end])->count();

        return round($failed / $total, 4);
    }

    protected function getRepeatRateComparison(array $current, array $previous): array
    {
        $currentRate = $this->calculateRepeatRate($current['start'], $current['end']);
        $previousRate = $this->calculateRepeatRate($previous['start'], $previous['end']);
        $description = __('Repeat purchase rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $current['start']->format('d M'), 'current_end' => $current['end']->format('d M'), 'previous_start' => $previous['start']->format('d M'), 'previous_end' => $previous['end']->format('d M')]);

        return $this->formatComparison($currentRate, $previousRate, 'percentage', ['description' => $description]);
    }

    protected function calculateRepeatRate(Carbon $start, Carbon $end): float
    {
        $totalCustomers = Order::query()->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id');
        if ($totalCustomers === 0) {
            return 0.0;
        }
        $repeatCustomers = Order::query()->select('customer_id')->where('payment_status', Order::STATUS_PAID)->whereBetween('created_at', [$start, $end])->groupBy('customer_id')->havingRaw('COUNT(*) > 1')->get()->count();

        return round($repeatCustomers / $totalCustomers, 4);
    }

    protected function getPeriodMetadata(array $current, array $previous): array
    {
        return ['current_period' => ['start' => $current['start']->toIso8601String(), 'end' => $current['end']->toIso8601String()], 'previous_period' => ['start' => $previous['start']->toIso8601String(), 'end' => $previous['end']->toIso8601String()], 'currency' => config('app.currency', 'USD'), 'generated_at' => now()->toIso8601String()];
    }

    protected function formatComparison($current, $previous, string $type = 'number', array $additional = []): array
    {
        $absoluteChange = $current - $previous;
        $percentageChange = $previous != 0 ? ($current - $previous) / abs($previous) * 100 : 0;
        $formatted = ['current' => $this->formatValue($current, $type), 'previous' => $this->formatValue($previous, $type), 'change_absolute' => $this->formatValue($absoluteChange, $type), 'change_percentage' => round($percentageChange, 2), 'trend' => $absoluteChange > 0 ? 'up' : ($absoluteChange < 0 ? 'down' : 'flat')];
        if ($type === 'percentage') {
            $ppChange = round($absoluteChange * 100, 2);
            $formatted['change'] = ($ppChange >= 0 ? '+' : '').$ppChange.'pp';
        } else {
            $formatted['change'] = ($percentageChange >= 0 ? '+' : '').round($percentageChange, 1).'%';
        }

        return array_merge($formatted, $additional);
    }

    protected function formatValue($value, string $type)
    {
        return match ($type) {
            'currency' => round($value, 2),
            'percentage' => round($value, 4),
            'count' => (int) $value,
            default => $value,
        };
    }
}
