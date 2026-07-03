<?php

namespace Coderstm\Services\Reports;

use Coderstm\Services\Reports\Acquisition\NewSignupsReport;
// Import new single-responsibility report classes
use Coderstm\Services\Reports\Acquisition\TrialConversionReport;
use Coderstm\Services\Reports\Coupons\CouponPerformanceReport;
use Coderstm\Services\Reports\Coupons\CouponRedemptionReport;
use Coderstm\Services\Reports\Coupons\DiscountImpactReport;
use Coderstm\Services\Reports\Economics\ArpuReport;
use Coderstm\Services\Reports\Economics\CacLtvReport;
use Coderstm\Services\Reports\Economics\ClvReport;
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

/**
 * Maps report types to their corresponding service classes.
 *
 * This class centralizes the mapping logic and provides methods
 * to resolve, validate, and query available report types.
 *
 * Each report type maps to a single-responsibility report class
 * that extends AbstractReport for consistent behavior and
 * memory-efficient cursor-based streaming.
 */
class ReportService
{
    /**
     * Map of report types to their service classes.
     *
     * Each report type maps to ONE class (single-responsibility pattern).
     *
     * @var array<string, class-string<ReportInterface>>
     */
    /**
     * Map of report types to their service classes.
     *
     * Each report type maps to ONE class (single-responsibility pattern).
     *
     * @var array<string, class-string<ReportInterface>>
     */
    protected static array $map = [
        // Revenue & Subscription Reports
        'mrr-by-plan' => MrrByPlanReport::class,
        'active-subscriptions-time' => ActiveSubscriptionsTimeReport::class,
        'mrr-movement' => MrrMovementReport::class,

        // Subscription Lifecycle Reports
        'subscription-lifecycle' => SubscriptionLifecycleReport::class,
        'freeze-usage' => FreezeUsageReport::class,
        'contract-progress' => ContractProgressReport::class,
        'renewal-forecast' => RenewalForecastReport::class,

        // Retention & Churn Reports
        'customer-churn' => CustomerChurnReport::class,
        'mrr-churn' => MrrChurnReport::class,
        'member-retention' => MemberRetentionReport::class,

        // Economics & Unit Metrics
        'arpu' => ArpuReport::class,
        'clv' => ClvReport::class,
        'cac-ltv' => CacLtvReport::class,

        // Acquisition & Conversion
        'trial-conversion' => TrialConversionReport::class,
        'new-signups' => NewSignupsReport::class,

        // Order & Sales Reports
        'sales-summary' => SalesSummaryReport::class,
        'payment-performance' => PaymentPerformanceReport::class,
        'fulfillment-analysis' => FulfillmentAnalysisReport::class,
        'refund-analysis' => RefundAnalysisReport::class,
        'tax-summary' => TaxSummaryReport::class,

        // Plan Analysis Reports
        'plan-comparison' => PlanComparisonReport::class,
        'plan-revenue-breakdown' => PlanRevenueBreakdownReport::class,
        'feature-utilization' => FeatureUtilizationReport::class,

        // Coupons & Discounts Reports
        'coupon-performance' => CouponPerformanceReport::class,
        'coupon-redemption' => CouponRedemptionReport::class,
        'discount-impact' => DiscountImpactReport::class,

        // Data Exports (raw data)
        'users' => UsersExportReport::class,
        'subscriptions' => SubscriptionsExportReport::class,
        'orders' => OrdersExportReport::class,
        'customers' => CustomersExportReport::class,
        'payments' => PaymentsExportReport::class,
    ];

    /**
     * Grouping of report types by category.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $grouped = [
        'revenue' => [
            'mrr-by-plan',
            'active-subscriptions-time',
            'mrr-movement',
        ],
        'subscriptions' => [
            'subscription-lifecycle',
            'freeze-usage',
            'contract-progress',
            'renewal-forecast',
        ],
        'retention' => [
            'customer-churn',
            'mrr-churn',
            'member-retention',
        ],
        'economics' => [
            'arpu',
            'clv',
            'cac-ltv',
        ],
        'acquisition' => [
            'trial-conversion',
            'new-signups',
        ],
        'orders' => [
            'sales-summary',
            'payment-performance',
            'fulfillment-analysis',
            'refund-analysis',
            'tax-summary',
        ],
        'plans' => [
            'plan-comparison',
            'plan-revenue-breakdown',
            'feature-utilization',
        ],
        'coupons' => [
            'coupon-performance',
            'coupon-redemption',
            'discount-impact',
        ],
        'exports' => [
            'users',
            'subscriptions',
            'orders',
            'customers',
            'payments',
        ],
    ];

    /**
     * Map of report types to their human-readable labels.
     *
     * @var array<string, string>
     */
    protected static array $labels = [
        // Revenue & Subscription Reports
        'mrr-by-plan' => 'MRR by Plan',
        'active-subscriptions-time' => 'Active Subscriptions Over Time',
        'mrr-movement' => 'MRR Movement',

        // Subscription Lifecycle Reports
        'subscription-lifecycle' => 'Subscription Lifecycle Analysis',
        'freeze-usage' => 'Membership Freeze Usage',
        'contract-progress' => 'Contract Progress & Completion',
        'renewal-forecast' => 'Renewal Forecast',

        // Retention & Churn Reports
        'customer-churn' => 'Customer Churn Rate',
        'mrr-churn' => 'MRR Churn Rate',
        'member-retention' => 'Member Retention Cohort',

        // Economics & Unit Metrics
        'arpu' => 'Average Revenue Per User (ARPU)',
        'clv' => 'Customer Lifetime Value (CLV)',
        'cac-ltv' => 'CAC vs LTV',

        // Acquisition & Conversion
        'trial-conversion' => 'Trial Conversion Rate',
        'new-signups' => 'New Signups',

        // Order & Sales Reports
        'sales-summary' => 'Sales Summary',
        'payment-performance' => 'Payment Performance',
        'fulfillment-analysis' => 'Fulfillment Analysis',
        'refund-analysis' => 'Refund Analysis',
        'tax-summary' => 'Tax Summary',

        // Plan Analysis Reports
        'plan-comparison' => 'Plan Comparison',
        'plan-revenue-breakdown' => 'Plan Revenue Breakdown',
        'feature-utilization' => 'Feature Utilization',

        // Coupons & Discounts
        'coupon-performance' => 'Coupon Performance',
        'coupon-redemption' => 'Coupon Redemption History',
        'discount-impact' => 'Discount Impact Analysis',

        // Data Exports
        'users' => 'Users Export',
        'subscriptions' => 'Subscriptions Export',
        'orders' => 'Orders Export',
        'customers' => 'Customers Export',
        'payments' => 'Payments Export',
        'checkouts' => 'Checkouts Export',
    ];

    /**
     * Map of report categories to their human-readable labels.
     *
     * @var array<string, string>
     */
    protected static array $categoryLabels = [
        'revenue' => 'Revenue & Subscriptions',
        'subscriptions' => 'Subscription Lifecycle',
        'retention' => 'Retention & Churn',
        'economics' => 'Economics & Unit Metrics',
        'acquisition' => 'Acquisition & Conversion',
        'orders' => 'Order & Sales',
        'plans' => 'Plans',
        'coupons' => 'Coupons & Discounts',
        'exports' => 'Data Exports',
    ];

    /**
     * Resolve and instantiate the service for a given report type.
     *
     * @param  string  $type  The report type
     *
     * @throws InvalidArgumentException If the report type is not supported
     */
    public static function resolve(string $type): ReportInterface
    {
        if (! static::has($type)) {
            throw new InvalidArgumentException("Unknown report type: {$type}");
        }

        $serviceClass = static::$map[$type];

        return new $serviceClass;
    }

    /**
     * Get the service class for a given report type.
     *
     * @param  string  $type  The report type
     * @return class-string<ReportInterface>|null
     */
    public static function getServiceClass(string $type): ?string
    {
        return static::$map[$type] ?? null;
    }

    /**
     * Check if a report type is supported.
     *
     * @param  string  $type  The report type
     */
    public static function has(string $type): bool
    {
        return isset(static::$map[$type]);
    }

    /**
     * Get all supported report types.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(static::$map);
    }

    /**
     * Get all report types grouped by category.
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return static::$grouped;
    }

    /**
     * Get the category for a given report type.
     *
     * @param  string  $type  The report type
     */
    public static function getCategory(string $type): ?string
    {
        foreach (static::grouped() as $category => $types) {
            if (in_array($type, $types)) {
                return $category;
            }
        }

        return null;
    }

    /**
     * Get all report types for a specific category.
     *
     * @param  string  $category  The category name
     * @return array<int, string>
     */
    public static function forCategory(string $category): array
    {
        return static::grouped()[$category] ?? [];
    }

    /**
     * Register a custom report type mapping.
     *
     * Useful for modules to add their own custom reports.
     *
     * @param  string  $type  The report type
     * @param  class-string<ReportInterface>  $serviceClass  The service class
     * @param  string|null  $label  The human-readable label
     * @param  string|null  $category  The category key
     */
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

    /**
     * Register a new report category.
     *
     * @param  string  $key  The category key
     * @param  string  $label  The human-readable label
     */
    public static function registerCategory(string $key, string $label): void
    {
        static::$categoryLabels[$key] = $label;
        if (! isset(static::$grouped[$key])) {
            static::$grouped[$key] = [];
        }
    }

    /**
     * Unregister a report type mapping.
     *
     * @param  string  $type  The report type
     */
    public static function unregister(string $type): void
    {
        unset(static::$map[$type]);
        unset(static::$labels[$type]);

        foreach (static::$grouped as $category => $types) {
            if (($key = array_search($type, $types)) !== false) {
                unset(static::$grouped[$category][$key]);
                // Re-index array
                static::$grouped[$category] = array_values(static::$grouped[$category]);
            }
        }
    }

    /**
     * Get human-readable label for a report type.
     *
     * @param  string  $type  The report type
     */
    public static function getLabel(string $type): string
    {
        return static::$labels[$type] ?? ucwords(str_replace(['-', '_'], ' ', $type));
    }

    /**
     * Get all report types with their labels.
     *
     * @return array<string, string>
     */
    public static function allWithLabels(): array
    {
        $types = static::all();
        $result = [];

        foreach ($types as $type) {
            $result[$type] = static::getLabel($type);
        }

        return $result;
    }

    /**
     * Get human-readable labels for report categories.
     *
     * @return array<string, string>
     */
    public static function getCategoryLabels(): array
    {
        return static::$categoryLabels;
    }
}
