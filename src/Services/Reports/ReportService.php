<?php

namespace Coderstm\Services\Reports;

use Coderstm\Services\Reports\Acquisition\NewSignupsReport;
use Coderstm\Services\Reports\Acquisition\TrialConversionReport;
use Coderstm\Services\Reports\Checkout\AbandonedCartDetailReport;
use Coderstm\Services\Reports\Checkout\CheckoutFunnelReport;
use Coderstm\Services\Reports\Checkout\CheckoutRecoveryReport;
use Coderstm\Services\Reports\Checkout\PaymentMethodPerformanceReport;
use Coderstm\Services\Reports\Coupons\CouponPerformanceReport;
use Coderstm\Services\Reports\Coupons\CouponRedemptionReport;
use Coderstm\Services\Reports\Coupons\DiscountImpactReport;
use Coderstm\Services\Reports\Economics\ArpuReport;
use Coderstm\Services\Reports\Economics\CacLtvReport;
use Coderstm\Services\Reports\Economics\ClvReport;
use Coderstm\Services\Reports\Exports\CheckoutsExportReport;
use Coderstm\Services\Reports\Exports\CustomersExportReport;
use Coderstm\Services\Reports\Exports\OrdersExportReport;
use Coderstm\Services\Reports\Exports\PaymentsExportReport;
use Coderstm\Services\Reports\Exports\SubscriptionsExportReport;
use Coderstm\Services\Reports\Exports\UsersExportReport;
use Coderstm\Services\Reports\Orders\FulfillmentAnalysisReport;
use Coderstm\Services\Reports\Orders\PaymentPerformanceReport;
use Coderstm\Services\Reports\Orders\RefundAnalysisReport;
use Coderstm\Services\Reports\Orders\SalesSummaryReport;
use Coderstm\Services\Reports\Orders\TaxSummaryReport;
use Coderstm\Services\Reports\Plans\FeatureUtilizationReport;
use Coderstm\Services\Reports\Plans\PlanComparisonReport;
use Coderstm\Services\Reports\Plans\PlanRevenueBreakdownReport;
use Coderstm\Services\Reports\Retention\CustomerChurnReport;
use Coderstm\Services\Reports\Retention\MemberRetentionReport;
use Coderstm\Services\Reports\Retention\MrrChurnReport;
use Coderstm\Services\Reports\Revenue\ActiveSubscriptionsTimeReport;
use Coderstm\Services\Reports\Revenue\MrrByPlanReport;
use Coderstm\Services\Reports\Revenue\MrrMovementReport;
use Coderstm\Services\Reports\Subscriptions\ContractProgressReport;
use Coderstm\Services\Reports\Subscriptions\FreezeUsageReport;
use Coderstm\Services\Reports\Subscriptions\RenewalForecastReport;
use Coderstm\Services\Reports\Subscriptions\SubscriptionLifecycleReport;
use InvalidArgumentException;

class ReportService
{
    protected static array $map = ['mrr-by-plan' => MrrByPlanReport::class, 'active-subscriptions-time' => ActiveSubscriptionsTimeReport::class, 'mrr-movement' => MrrMovementReport::class, 'subscription-lifecycle' => SubscriptionLifecycleReport::class, 'freeze-usage' => FreezeUsageReport::class, 'contract-progress' => ContractProgressReport::class, 'renewal-forecast' => RenewalForecastReport::class, 'customer-churn' => CustomerChurnReport::class, 'mrr-churn' => MrrChurnReport::class, 'member-retention' => MemberRetentionReport::class, 'arpu' => ArpuReport::class, 'clv' => ClvReport::class, 'cac-ltv' => CacLtvReport::class, 'trial-conversion' => TrialConversionReport::class, 'new-signups' => NewSignupsReport::class, 'sales-summary' => SalesSummaryReport::class, 'payment-performance' => PaymentPerformanceReport::class, 'fulfillment-analysis' => FulfillmentAnalysisReport::class, 'refund-analysis' => RefundAnalysisReport::class, 'tax-summary' => TaxSummaryReport::class, 'checkout-funnel' => CheckoutFunnelReport::class, 'abandoned-cart-detail' => AbandonedCartDetailReport::class, 'payment-method-performance' => PaymentMethodPerformanceReport::class, 'checkout-recovery' => CheckoutRecoveryReport::class, 'plan-comparison' => PlanComparisonReport::class, 'plan-revenue-breakdown' => PlanRevenueBreakdownReport::class, 'feature-utilization' => FeatureUtilizationReport::class, 'coupon-performance' => CouponPerformanceReport::class, 'coupon-redemption' => CouponRedemptionReport::class, 'discount-impact' => DiscountImpactReport::class, 'users' => UsersExportReport::class, 'subscriptions' => SubscriptionsExportReport::class, 'orders' => OrdersExportReport::class, 'customers' => CustomersExportReport::class, 'payments' => PaymentsExportReport::class, 'checkouts' => CheckoutsExportReport::class];

    protected static array $grouped = ['revenue' => ['mrr-by-plan', 'active-subscriptions-time', 'mrr-movement'], 'subscriptions' => ['subscription-lifecycle', 'freeze-usage', 'contract-progress', 'renewal-forecast'], 'retention' => ['customer-churn', 'mrr-churn', 'member-retention'], 'economics' => ['arpu', 'clv', 'cac-ltv'], 'acquisition' => ['trial-conversion', 'new-signups'], 'orders' => ['sales-summary', 'payment-performance', 'fulfillment-analysis', 'refund-analysis', 'tax-summary'], 'checkout' => ['checkout-funnel', 'abandoned-cart-detail', 'payment-method-performance', 'checkout-recovery'], 'plans' => ['plan-comparison', 'plan-revenue-breakdown', 'feature-utilization'], 'coupons' => ['coupon-performance', 'coupon-redemption', 'discount-impact'], 'exports' => ['users', 'subscriptions', 'orders', 'customers', 'payments', 'checkouts']];

    protected static array $labels = ['mrr-by-plan' => 'MRR by Plan', 'active-subscriptions-time' => 'Active Subscriptions Over Time', 'mrr-movement' => 'MRR Movement', 'subscription-lifecycle' => 'Subscription Lifecycle Analysis', 'freeze-usage' => 'Membership Freeze Usage', 'contract-progress' => 'Contract Progress & Completion', 'renewal-forecast' => 'Renewal Forecast', 'customer-churn' => 'Customer Churn Rate', 'mrr-churn' => 'MRR Churn Rate', 'member-retention' => 'Member Retention Cohort', 'arpu' => 'Average Revenue Per User (ARPU)', 'clv' => 'Customer Lifetime Value (CLV)', 'cac-ltv' => 'CAC vs LTV', 'trial-conversion' => 'Trial Conversion Rate', 'new-signups' => 'New Signups', 'sales-summary' => 'Sales Summary', 'payment-performance' => 'Payment Performance', 'fulfillment-analysis' => 'Fulfillment Analysis', 'refund-analysis' => 'Refund Analysis', 'tax-summary' => 'Tax Summary', 'checkout-funnel' => 'Checkout Funnel', 'abandoned-cart-detail' => 'Abandoned Cart Details', 'payment-method-performance' => 'Payment Method Performance', 'checkout-recovery' => 'Checkout Recovery', 'plan-comparison' => 'Plan Comparison', 'plan-revenue-breakdown' => 'Plan Revenue Breakdown', 'feature-utilization' => 'Feature Utilization', 'coupon-performance' => 'Coupon Performance', 'coupon-redemption' => 'Coupon Redemption History', 'discount-impact' => 'Discount Impact Analysis', 'users' => 'Users Export', 'subscriptions' => 'Subscriptions Export', 'orders' => 'Orders Export', 'customers' => 'Customers Export', 'payments' => 'Payments Export', 'checkouts' => 'Checkouts Export'];

    protected static array $categoryLabels = ['revenue' => 'Revenue & Subscriptions', 'subscriptions' => 'Subscription Lifecycle', 'retention' => 'Retention & Churn', 'economics' => 'Economics & Unit Metrics', 'acquisition' => 'Acquisition & Conversion', 'orders' => 'Order & Sales', 'checkout' => 'Checkout & Cart', 'plans' => 'Plans', 'coupons' => 'Coupons & Discounts', 'exports' => 'Data Exports'];

    public static function resolve(string $type): ReportInterface
    {
        if (! static::has($type)) {
            throw new InvalidArgumentException("Unknown report type: {$type}");
        }
        $serviceClass = static::$map[$type];

        return new $serviceClass;
    }

    public static function getServiceClass(string $type): ?string
    {
        return static::$map[$type] ?? null;
    }

    public static function has(string $type): bool
    {
        return isset(static::$map[$type]);
    }

    public static function all(): array
    {
        return array_keys(static::$map);
    }

    public static function grouped(): array
    {
        return static::$grouped;
    }

    public static function getCategory(string $type): ?string
    {
        foreach (static::grouped() as $category => $types) {
            if (in_array($type, $types)) {
                return $category;
            }
        }

        return null;
    }

    public static function forCategory(string $category): array
    {
        return static::grouped()[$category] ?? [];
    }

    public static function register(string $type, string $serviceClass, ?string $label = null, ?string $category = null): void
    {
        static::$map[$type] = $serviceClass;
        if ($label) {
            static::$labels[$type] = $label;
        }
        if ($category) {
            if (! isset(static::$grouped[$category])) {
                static::$grouped[$category] = [];
            }
            if (! in_array($type, static::$grouped[$category])) {
                static::$grouped[$category][] = $type;
            }
        }
    }

    public static function registerCategory(string $key, string $label): void
    {
        static::$categoryLabels[$key] = $label;
        if (! isset(static::$grouped[$key])) {
            static::$grouped[$key] = [];
        }
    }

    public static function unregister(string $type): void
    {
        unset(static::$map[$type]);
        unset(static::$labels[$type]);
        foreach (static::$grouped as $category => $types) {
            if (($key = array_search($type, $types)) !== false) {
                unset(static::$grouped[$category][$key]);
                static::$grouped[$category] = array_values(static::$grouped[$category]);
            }
        }
    }

    public static function getLabel(string $type): string
    {
        return static::$labels[$type] ?? ucwords(str_replace(['-', '_'], ' ', $type));
    }

    public static function allWithLabels(): array
    {
        $types = static::all();
        $result = [];
        foreach ($types as $type) {
            $result[$type] = static::getLabel($type);
        }

        return $result;
    }

    public static function getCategoryLabels(): array
    {
        return static::$categoryLabels;
    }
}
