<?php

namespace Coderstm\Services\Metrics;

use Carbon\Carbon;
use Coderstm\Coderstm;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription;
use Illuminate\Support\Facades\DB;

class CustomerMetrics extends MetricsCalculator
{
    protected string $cachePrefix = 'customer_metrics';

    public function getTotalCount(): int
    {
        return $this->remember('total_count', function () {
            return Coderstm::$userModel::count();
        });
    }

    public function getNewCustomers(): int
    {
        return $this->remember('new_customers', function () {
            $range = $this->getDateRange();

            return Coderstm::$userModel::query()->whereBetween('created_at', [$range['start'], $range['end']])->count();
        });
    }

    public function getGrowthRate(): float
    {
        return $this->remember('growth_rate', function () {
            $currentMonth = Coderstm::$userModel::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
            $previousMonth = Coderstm::$userModel::query()->whereMonth('created_at', now()->subMonth()->month)->whereYear('created_at', now()->subMonth()->year)->count();
            if ($previousMonth === 0) {
                return $currentMonth > 0 ? 100.0 : 0.0;
            }

            return round(($currentMonth - $previousMonth) / $previousMonth * 100, 2);
        });
    }

    public function getActiveSubscribers(): int
    {
        return $this->remember('active_subscribers', function () {
            return Subscription::query()->active()->distinct('user_id')->count('user_id');
        });
    }

    public function getSubscriptionAdoptionRate(): float
    {
        return $this->remember('subscription_adoption_rate', function () {
            $totalCustomers = $this->getTotalCount();
            if ($totalCustomers === 0) {
                return 0.0;
            }
            $subscribers = $this->getActiveSubscribers();

            return round($subscribers / $totalCustomers * 100, 2);
        });
    }

    public function getTopByRevenue(int $limit = 10): array
    {
        return $this->remember("top_by_revenue_{$limit}", function () use ($limit) {
            $range = $this->getDateRange();

            return Coderstm::$userModel::query()->select('users.id', 'users.first_name', 'users.last_name', 'users.email')->selectRaw('SUM(orders.grand_total) as total_spent')->join('orders', 'users.id', '=', 'orders.customer_id')->where('orders.payment_status', 'paid')->whereBetween('orders.created_at', [$range['start'], $range['end']])->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')->orderByDesc('total_spent')->limit($limit)->get()->toArray();
        });
    }

    public function getRepeatPurchaseRate(): float
    {
        return $this->remember('repeat_purchase_rate', function () {
            $range = $this->getDateRange();
            $customersWithOrders = DB::table('orders')->where('payment_status', 'paid')->whereBetween('created_at', [$range['start'], $range['end']])->distinct('customer_id')->count('customer_id');
            if ($customersWithOrders === 0) {
                return 0.0;
            }
            $repeatCustomers = DB::table('orders')->select('customer_id')->where('payment_status', 'paid')->whereBetween('created_at', [$range['start'], $range['end']])->groupBy('customer_id')->havingRaw('COUNT(*) > 1')->get()->count();

            return round($repeatCustomers / $customersWithOrders * 100, 2);
        });
    }

    public function getAverageValue(): float
    {
        return $this->remember('average_value', function () {
            $range = $this->getDateRange();
            $totalRevenue = Order::query()->where('payment_status', 'paid')->whereBetween('created_at', [$range['start'], $range['end']])->sum('grand_total') ?? 0.0;
            $customersWithOrders = Order::query()->where('payment_status', 'paid')->whereBetween('created_at', [$range['start'], $range['end']])->distinct('customer_id')->count('customer_id');

            return $customersWithOrders > 0 ? round($totalRevenue / $customersWithOrders, 2) : 0.0;
        });
    }

    public function getCLV(): float
    {
        return $this->remember('clv', function () {
            $avgRevenue = DB::table('users')->select(DB::raw('AVG(total_revenue) as avg_revenue'))->fromSub(function ($query) {
                $query->from('users')->select('users.id')->selectRaw('COALESCE(SUM(orders.grand_total), 0) as total_revenue')->leftJoin('orders', function ($join) {
                    $join->on('users.id', '=', 'orders.customer_id')->where('orders.payment_status', 'paid');
                })->groupBy('users.id');
            }, 'user_revenues')->value('avg_revenue');

            return round($avgRevenue ?? 0, 2);
        });
    }

    public function getSegments(): array
    {
        return $this->remember('segments', function () {
            $segments = DB::table('users')->select(DB::raw('CASE
                        WHEN total_spent >= 1000 THEN "high_value"
                        WHEN total_spent >= 100 THEN "medium_value"
                        WHEN total_spent > 0 THEN "low_value"
                        ELSE "no_purchase"
                    END as segment'), DB::raw('COUNT(*) as count'))->fromSub(function ($query) {
                $query->from('users')->select('users.id')->selectRaw('COALESCE(SUM(orders.grand_total), 0) as total_spent')->leftJoin('orders', function ($join) {
                    $join->on('users.id', '=', 'orders.customer_id')->where('orders.payment_status', 'paid');
                })->groupBy('users.id');
            }, 'customer_totals')->groupBy('segment')->get()->pluck('count', 'segment')->toArray();

            return array_merge(['high_value' => 0, 'medium_value' => 0, 'low_value' => 0, 'no_purchase' => 0], $segments);
        });
    }

    public function getAtRiskCount(): int
    {
        return $this->remember('at_risk_count', function () {
            $range = $this->getDateRange();

            return Subscription::query()->whereNotNull('canceled_at')->whereBetween('canceled_at', [$range['start'], $range['end']])->distinct('user_id')->count('user_id');
        });
    }

    public function get(): array
    {
        $payload = ['total_count' => $this->getTotalCount(), 'new_customers' => $this->getNewCustomers(), 'growth_rate' => $this->getGrowthRate(), 'active_subscribers' => $this->getActiveSubscribers(), 'subscription_adoption_rate' => $this->getSubscriptionAdoptionRate(), 'top_by_revenue' => $this->getTopByRevenue(), 'repeat_purchase_rate' => $this->getRepeatPurchaseRate(), 'average_value' => $this->getAverageValue(), 'clv' => $this->getCLV(), 'segments' => $this->getSegments(), 'at_risk_count' => $this->getAtRiskCount(), 'metadata' => $this->getMetadata()];
        $periods = $this->getComparisonPeriods();

        return $this->withComparisons($payload, ['new_customers' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->newCustomersBetween($start, $end), 'description' => __('New customers from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'repeat_purchase_rate' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->repeatRateBetween($start, $end), 'type' => 'percentage', 'description' => __('Repeat purchase rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'average_value' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->averageValueBetween($start, $end), 'type' => 'currency', 'description' => __('Average customer value from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'at_risk_count' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->atRiskBetween($start, $end), 'description' => __('At-risk customers from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])]]);
    }

    protected function newCustomersBetween(Carbon $start, Carbon $end): int
    {
        return Coderstm::$userModel::query()->whereBetween('created_at', [$start, $end])->count();
    }

    protected function repeatRateBetween(Carbon $start, Carbon $end): float
    {
        $customersWithOrders = DB::table('orders')->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id');
        if ($customersWithOrders === 0) {
            return 0.0;
        }
        $repeatCustomers = DB::table('orders')->select('customer_id')->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->groupBy('customer_id')->havingRaw('COUNT(*) > 1')->get()->count();

        return round($repeatCustomers / $customersWithOrders * 100, 2);
    }

    protected function averageValueBetween(Carbon $start, Carbon $end): float
    {
        $totalRevenue = Order::query()->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->sum('grand_total') ?? 0.0;
        $customersWithOrders = Order::query()->where('payment_status', 'paid')->whereBetween('created_at', [$start, $end])->distinct('customer_id')->count('customer_id');

        return $customersWithOrders > 0 ? round($totalRevenue / $customersWithOrders, 2) : 0.0;
    }

    protected function atRiskBetween(Carbon $start, Carbon $end): int
    {
        return Subscription::query()->whereNotNull('canceled_at')->whereBetween('canceled_at', [$start, $end])->distinct('user_id')->count('user_id');
    }
}
