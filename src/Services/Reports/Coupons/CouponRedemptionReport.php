<?php

namespace Coderstm\Services\Reports\Coupons;

use Coderstm\Coderstm;
use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

/**
 * Coupon Redemption Report
 *
 * Detailed redemption history with user and order details.
 * Supports both authenticated users and guest checkouts.
 */
class CouponRedemptionReport extends AbstractReport
{
    /**
     * {@inheritdoc}
     */
    protected array $columns = [
        'redemption_date' => ['label' => 'Redemption Date', 'type' => 'text'],
        'coupon_code' => ['label' => 'Coupon Code', 'type' => 'text'],
        'user_email' => ['label' => 'User Email', 'type' => 'text'],
        'order_id' => ['label' => 'Order ID', 'type' => 'number'],
        'order_total' => ['label' => 'Order Total', 'type' => 'currency'],
        'discount_amount' => ['label' => 'Discount Amount', 'type' => 'currency'],
        'final_total' => ['label' => 'Final Total', 'type' => 'currency'],
        'discount_type' => ['label' => 'Discount Type', 'type' => 'text'],
    ];

    /**
     * {@inheritdoc}
     */
    public static function getType(): string
    {
        return 'coupon-redemption';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Track detailed coupon redemption transactions';
    }

    /**
     * Build the base query with all redemption details.
     *
     * Uses LEFT JOINs to include guest checkouts and database-agnostic
     * COALESCE for NULL handling.
     *
     * {@inheritdoc}
     */
    public function query(array $filters)
    {
        $coalesce = $this->dbCoalesce(['users.email', '"Guest"']);

        return Coderstm::$orderModel::query()->toBase()
            ->join('discount_lines', function ($join) {
                $join->on('discount_lines.discountable_id', '=', 'orders.id')
                    ->whereRaw("discount_lines.discountable_type LIKE '%Order%'");
            })
            ->join('coupons', 'discount_lines.coupon_id', '=', 'coupons.id')
            ->leftJoin('users', 'orders.customer_id', '=', 'users.id')
            ->whereBetween('orders.created_at', [$filters['from'], $filters['to']])
            ->whereNotNull('discount_lines.coupon_id')
            ->select([
                'orders.created_at as redemption_date',
                'coupons.promotion_code as coupon_code',
                DB::raw("{$coalesce} as user_email"),
                'orders.id as order_id',
                DB::raw('COALESCE(orders.grand_total, 0) as order_total'),
                DB::raw('COALESCE(discount_lines.value, 0) as discount_amount'),
                'coupons.discount_type',
            ])
            ->orderBy('orders.created_at', 'desc');
    }

    /**
     * Transform row to array with raw numeric values.
     *
     * {@inheritdoc}
     */
    public function toRow($row): array
    {
        $orderTotal = (float) ($row->order_total ?? 0);
        $discountAmount = (float) ($row->discount_amount ?? 0);
        $finalTotal = $orderTotal - $discountAmount;

        return [
            'redemption_date' => $row->redemption_date ?? '',
            'coupon_code' => $row->coupon_code ?? '',
            'user_email' => $row->user_email ?? 'Guest',
            'order_id' => $row->order_id ? (int) $row->order_id : null,
            'order_total' => $orderTotal,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'discount_type' => $row->discount_type ?? 'percentage',
        ];
    }

    /**
     * Calculate summary statistics.
     *
     * {@inheritdoc}
     */
    public function summarize(array $filters): array
    {
        $stats = Coderstm::$orderModel::query()->toBase()
            ->join('discount_lines', function ($join) {
                $join->on('discount_lines.discountable_id', '=', 'orders.id')
                    ->where('discount_lines.discountable_type', 'like', '%Order%');
            })
            ->whereBetween('orders.created_at', [$filters['from'], $filters['to']])
            ->whereNotNull('coupon_id')
            ->select([
                DB::raw('COUNT(*) as total_redemptions'),
                DB::raw('SUM(discount_lines.value) as total_discount'),
            ])
            ->first();

        return [
            'total_redemptions' => (int) ($stats->total_redemptions ?? 0),
            'total_discount_given' => format_amount($stats->total_discount ?? 0),
        ];
    }
}
