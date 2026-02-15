<?php

namespace Coderstm\Services\Metrics;

use Carbon\Carbon;
use Coderstm\Models\Shop\Checkout;
use Illuminate\Support\Facades\DB;

class CheckoutMetrics extends MetricsCalculator
{
    protected string $cachePrefix = 'checkout_metrics';

    public function getTotalCount(): int
    {
        return $this->remember('total_count', function () {
            $range = $this->getDateRange();

            return Checkout::query()->whereBetween('created_at', [$range['start'], $range['end']])->count();
        });
    }

    public function getCompletedCount(): int
    {
        return $this->remember('completed_count', function () {
            $range = $this->getDateRange();

            return Checkout::query()->whereNotNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();
        });
    }

    public function getAbandonedCount(): int
    {
        return $this->remember('abandoned_count', function () {
            $range = $this->getDateRange();

            return Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();
        });
    }

    public function getPendingCount(): int
    {
        return $this->remember('pending_count', function () {
            return Checkout::query()->whereNull('completed_at')->whereNull('abandoned_at')->whereNotNull('started_at')->count();
        });
    }

    public function getConversionRate(): float
    {
        return $this->remember('conversion_rate', function () {
            $range = $this->getDateRange();
            $total = Checkout::query()->whereBetween('created_at', [$range['start'], $range['end']])->count();
            if ($total === 0) {
                return 0.0;
            }
            $completed = Checkout::query()->whereNotNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();

            return round($completed / $total * 100, 2);
        });
    }

    public function getAbandonmentRate(): float
    {
        return $this->remember('abandonment_rate', function () {
            $range = $this->getDateRange();
            $started = Checkout::query()->whereNotNull('started_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();
            if ($started === 0) {
                return 0.0;
            }
            $abandoned = Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();

            return round($abandoned / $started * 100, 2);
        });
    }

    public function getAbandonedCartValue(): float
    {
        return $this->remember('abandoned_cart_value', function () {
            $range = $this->getDateRange();

            return Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->sum('grand_total') ?? 0.0;
        });
    }

    public function getAvgAbandonedCartValue(): float
    {
        return $this->remember('avg_abandoned_cart_value', function () {
            $range = $this->getDateRange();

            return round(Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->avg('grand_total') ?? 0.0, 2);
        });
    }

    public function getRecoveryMetrics(): array
    {
        return $this->remember('recovery_metrics', function () {
            $range = $this->getDateRange();
            $emailsSent = Checkout::query()->whereNotNull('recovery_email_sent_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $recovered = Checkout::query()->where('recovery_status', Checkout::STATUS_RECOVERED)->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $recoveredValue = Checkout::query()->where('recovery_status', Checkout::STATUS_RECOVERED)->whereBetween('created_at', [$range['start'], $range['end']])->sum('grand_total') ?? 0.0;

            return ['emails_sent' => $emailsSent, 'recovered_count' => $recovered, 'recovered_value' => round($recoveredValue, 2), 'recovery_rate' => $emailsSent > 0 ? round($recovered / $emailsSent * 100, 2) : 0.0];
        });
    }

    public function getByStatus(): array
    {
        return $this->remember('by_status', function () {
            $range = $this->getDateRange();

            return Checkout::query()->select('status', DB::raw('COUNT(*) as count'))->whereBetween('created_at', [$range['start'], $range['end']])->groupBy('status')->get()->pluck('count', 'status')->toArray();
        });
    }

    public function getByType(): array
    {
        return $this->remember('by_type', function () {
            $range = $this->getDateRange();

            return Checkout::query()->select('type', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as value'))->whereBetween('created_at', [$range['start'], $range['end']])->groupBy('type')->get()->mapWithKeys(function ($item) {
                return [$item->type => ['count' => $item->count, 'value' => round($item->value, 2)]];
            })->toArray();
        });
    }

    public function getAvgCheckoutDuration(): float
    {
        return $this->remember('avg_checkout_duration', function () {
            $range = $this->getDateRange();
            $checkouts = Checkout::query()->whereNotNull('started_at')->whereNotNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->get(['started_at', 'completed_at']);
            if ($checkouts->isEmpty()) {
                return 0.0;
            }
            $totalMinutes = $checkouts->sum(function ($checkout) {
                return $checkout->started_at->diffInMinutes($checkout->completed_at);
            });

            return round($totalMinutes / $checkouts->count(), 2);
        });
    }

    public function getFunnelMetrics(): array
    {
        return $this->remember('funnel_metrics', function () {
            $range = $this->getDateRange();
            $cartCreated = Checkout::query()->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $checkoutStarted = Checkout::query()->whereNotNull('started_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $emailProvided = Checkout::query()->whereNotNull('email')->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $shippingFilled = Checkout::query()->whereNotNull('shipping_address')->whereBetween('created_at', [$range['start'], $range['end']])->count();
            $paymentCompleted = Checkout::query()->whereNotNull('completed_at')->whereBetween('created_at', [$range['start'], $range['end']])->count();

            return ['stages' => [['stage' => 'cart_created', 'count' => $cartCreated, 'rate' => 100.0], ['stage' => 'checkout_started', 'count' => $checkoutStarted, 'rate' => $cartCreated > 0 ? round($checkoutStarted / $cartCreated * 100, 2) : 0.0], ['stage' => 'email_provided', 'count' => $emailProvided, 'rate' => $cartCreated > 0 ? round($emailProvided / $cartCreated * 100, 2) : 0.0], ['stage' => 'shipping_filled', 'count' => $shippingFilled, 'rate' => $cartCreated > 0 ? round($shippingFilled / $cartCreated * 100, 2) : 0.0], ['stage' => 'payment_completed', 'count' => $paymentCompleted, 'rate' => $cartCreated > 0 ? round($paymentCompleted / $cartCreated * 100, 2) : 0.0]], 'overall_conversion' => $cartCreated > 0 ? round($paymentCompleted / $cartCreated * 100, 2) : 0.0];
        });
    }

    public function getPendingRecoveryCount(): int
    {
        return $this->remember('pending_recovery', function () {
            $abandonedHours = config('coderstm.shop.abandoned_cart_hours', 2);

            return Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereNull('recovery_email_sent_at')->where('recovery_status', '!=', Checkout::STATUS_RECOVERED)->where('abandoned_at', '<=', now()->subHours($abandonedHours))->count();
        });
    }

    public function getPotentialRevenue(): float
    {
        return $this->remember('potential_revenue', function () {
            return Checkout::query()->whereNull('completed_at')->whereNull('abandoned_at')->whereNotNull('started_at')->sum('grand_total') ?? 0.0;
        });
    }

    public function get(): array
    {
        $payload = ['total_count' => $this->getTotalCount(), 'completed_count' => $this->getCompletedCount(), 'abandoned_count' => $this->getAbandonedCount(), 'pending_count' => $this->getPendingCount(), 'conversion_rate' => $this->getConversionRate(), 'abandonment_rate' => $this->getAbandonmentRate(), 'abandoned_cart_value' => $this->getAbandonedCartValue(), 'avg_abandoned_cart_value' => $this->getAvgAbandonedCartValue(), 'recovery_metrics' => $this->getRecoveryMetrics(), 'by_status' => $this->getByStatus(), 'by_type' => $this->getByType(), 'avg_checkout_duration' => $this->getAvgCheckoutDuration(), 'funnel_metrics' => $this->getFunnelMetrics(), 'pending_recovery_count' => $this->getPendingRecoveryCount(), 'potential_revenue' => $this->getPotentialRevenue(), 'metadata' => $this->getMetadata()];
        $periods = $this->getComparisonPeriods();

        return $this->withComparisons($payload, ['completed_count' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->completedBetween($start, $end), 'description' => __('Completed checkouts from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'abandoned_count' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->abandonedBetween($start, $end), 'description' => __('Abandoned checkouts from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'conversion_rate' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->conversionRateBetween($start, $end), 'type' => 'percentage', 'description' => __('Conversion rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])], 'abandonment_rate' => ['calculator' => fn (Carbon $start, Carbon $end) => $this->abandonmentRateBetween($start, $end), 'type' => 'percentage', 'description' => __('Abandonment rate from :current_start to :current_end compared with :previous_start to :previous_end', ['current_start' => $periods['current']['start']->format('d M'), 'current_end' => $periods['current']['end']->format('d M'), 'previous_start' => $periods['previous']['start']->format('d M'), 'previous_end' => $periods['previous']['end']->format('d M')])]]);
    }

    protected function completedBetween(Carbon $start, Carbon $end): int
    {
        return Checkout::query()->whereNotNull('completed_at')->whereBetween('created_at', [$start, $end])->count();
    }

    protected function abandonedBetween(Carbon $start, Carbon $end): int
    {
        return Checkout::query()->whereNotNull('abandoned_at')->whereNull('completed_at')->whereBetween('created_at', [$start, $end])->count();
    }

    protected function conversionRateBetween(Carbon $start, Carbon $end): float
    {
        $total = Checkout::query()->whereBetween('created_at', [$start, $end])->count();
        if ($total === 0) {
            return 0.0;
        }

        return round($this->completedBetween($start, $end) / $total * 100, 2);
    }

    protected function abandonmentRateBetween(Carbon $start, Carbon $end): float
    {
        $started = Checkout::query()->whereNotNull('started_at')->whereBetween('created_at', [$start, $end])->count();
        if ($started === 0) {
            return 0.0;
        }

        return round($this->abandonedBetween($start, $end) / $started * 100, 2);
    }
}
