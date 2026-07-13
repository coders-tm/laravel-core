<?php

namespace Coderstm\Services\Reports\Coupons;

use Coderstm\Coderstm;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Coupon Performance Report
 *
 * Analyzes coupon effectiveness with redemption counts, discounts given, and ROI.
 * ROI calculated as ((revenue - discount) / discount) * 100.
 */
class CouponPerformanceReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'coupon_id' => ['label' => 'Coupon ID', 'type' => 'number'],
        'coupon_code' => ['label' => 'Code', 'type' => 'text'],
        'type' => ['label' => 'Type', 'type' => 'text'],
        'discount_type' => ['label' => 'Discount Type', 'type' => 'text'],
        'value' => ['label' => 'Value', 'type' => 'number'],
        'times_used' => ['label' => 'Times Used', 'type' => 'number'],
        'total_discount' => ['label' => 'Total Discount Given', 'type' => 'currency'],
        'revenue_generated' => ['label' => 'Revenue Generated', 'type' => 'currency'],
        'roi' => ['label' => 'ROI', 'type' => 'percentage'],
        'status' => ['label' => 'Status', 'type' => 'text'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'coupon-performance';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Evaluate coupon effectiveness and ROI';
    }

    /**
     * Validate coupon-specific filters.
     *
     * @param  array  $input  Raw filter input
     * @return array Validated and normalized filters
     */
    public function validate(array $input): array
    {
        // Validate coupon-specific filters
        $validated = validator($input, [
            'coupon_id' => 'nullable|integer|exists:coupons,id',
            'type' => 'nullable|string|in:general,promotion',
            'discount_type' => 'nullable|string|in:percentage,fixed',
            'status' => 'nullable|string|in:active,inactive',
        ])->validate();

        // Merge with parent validation
        return parent::validate($validated);
    }

    /**
     * Build the base query with all metrics calculated in a single query.
     *
     * Uses LEFT JOINs to include coupons with zero redemptions and
     * literal values in WHERE clauses for database compatibility.
     *
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $ordersQuery = Coderstm::$orderModel::query()->select('*');

        return Coderstm::$couponModel::query()->toBase()
            ->leftJoin('discount_lines', function ($join) {
                $join->on('coupons.id', '=', 'discount_lines.coupon_id');
            })
            ->leftJoinSub($ordersQuery, 'orders', function ($join) use ($filters) {
                $join->on('discount_lines.discountable_id', '=', 'orders.id')
                    ->whereRaw("discount_lines.discountable_type LIKE '%Order%'")
                    ->whereBetween('orders.created_at', [$filters['from'], $filters['to']]);
            })
            ->select([
                'coupons.id as coupon_id',
                'coupons.promotion_code as coupon_code',
                'coupons.type',
                'coupons.discount_type',
                'coupons.value',
                'coupons.active',
                DB::raw('COUNT(DISTINCT discount_lines.id) as times_used'),
                DB::raw('COALESCE(SUM(discount_lines.value), 0) as total_discount'),
                DB::raw('COALESCE(SUM(orders.grand_total), 0) as revenue_generated'),
            ])
            ->groupBy('coupons.id', 'coupons.promotion_code', 'coupons.type', 'coupons.discount_type', 'coupons.value', 'coupons.active')
            ->orderBy('times_used', 'desc');
    }

    /**
     * Transform row to array with raw numeric values.
     *
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        $totalDiscount = (float) ($row->total_discount ?? 0);
        $revenueGenerated = (float) ($row->revenue_generated ?? 0);
        $roi = $totalDiscount > 0 ? (($revenueGenerated - $totalDiscount) / $totalDiscount) * 100 : 0;

        return [
            'coupon_id' => (int) $row->coupon_id,
            'coupon_code' => $row->coupon_code ?? '',
            'type' => $row->type ?? 'general',
            'discount_type' => $row->discount_type ?? 'percentage',
            'value' => (float) ($row->value ?? 0),
            'times_used' => (int) ($row->times_used ?? 0),
            'total_discount' => $totalDiscount,
            'revenue_generated' => $revenueGenerated,
            'roi' => (float) $roi,
            'status' => ($row->active ?? false) ? 'Active' : 'Inactive',
        ];
    }

    /**
     * Calculate summary statistics.
     *
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $ordersQuery = Coderstm::$orderModel::query()->select('*');

        $stats = Coderstm::$orderModel::query()->toBase()
            ->join('discount_lines', function ($join) {
                $join->on('discount_lines.discountable_id', '=', 'orders.id')
                    ->where('discount_lines.discountable_type', 'like', '%Order%');
            })
            ->whereBetween('orders.created_at', [$filters['from'], $filters['to']])
            ->whereNotNull('discount_lines.coupon_id')
            ->select([
                DB::raw('COUNT(*) as total_redemptions'),
                DB::raw('SUM(discount_lines.value) as total_discount'),
                DB::raw('COUNT(DISTINCT discount_lines.coupon_id) as unique_coupons'),
            ])
            ->first();

        $totalCoupons = Coderstm::$couponModel::query()->toBase()->count();

        return [
            'total_coupons' => (int) $totalCoupons,
            'active_coupons_used' => (int) ($stats->unique_coupons ?? 0),
            'total_redemptions' => (int) ($stats->total_redemptions ?? 0),
            'total_discount_given' => format_amount($stats->total_discount ?? 0),
        ];
    }
}
