<?php

namespace Coderstm\Services\Reports\Coupons;

use Coderstm\Services\Reports\AbstractReport;
use Illuminate\Support\Facades\DB;

class CouponRedemptionReport extends AbstractReport
{
    protected array $columns = ['redemption_date' => ['label' => 'Redemption Date', 'type' => 'text'], 'coupon_code' => ['label' => 'Coupon Code', 'type' => 'text'], 'user_email' => ['label' => 'User Email', 'type' => 'text'], 'order_id' => ['label' => 'Order ID', 'type' => 'number'], 'order_total' => ['label' => 'Order Total', 'type' => 'currency'], 'discount_amount' => ['label' => 'Discount Amount', 'type' => 'currency'], 'final_total' => ['label' => 'Final Total', 'type' => 'currency'], 'discount_type' => ['label' => 'Discount Type', 'type' => 'text']];

    public static function getType(): string
    {
        return 'coupon-redemption';
    }

    public function getDescription(): string
    {
        return 'Track detailed coupon redemption transactions';
    }

    public function query(array $filters)
    {
        $coalesce = $this->dbCoalesce(['users.email', '"Guest"']);

        return DB::table('discount_lines')->join('coupons', 'discount_lines.coupon_id', '=', 'coupons.id')->leftJoin(DB::raw('orders'), function ($join) {
            $join->on('discount_lines.discountable_id', '=', 'orders.id')->whereRaw("discount_lines.discountable_type LIKE '%Order%'");
        })->leftJoin('users', 'orders.customer_id', '=', 'users.id')->whereBetween('orders.created_at', [$filters['from'], $filters['to']])->whereNotNull('discount_lines.coupon_id')->select(['orders.created_at as redemption_date', 'coupons.promotion_code as coupon_code', DB::raw("{$coalesce} as user_email"), 'orders.id as order_id', DB::raw('COALESCE(orders.grand_total, 0) as order_total'), DB::raw('COALESCE(discount_lines.value, 0) as discount_amount'), 'coupons.discount_type'])->orderBy('orders.created_at', 'desc');
    }

    public function toRow($row): array
    {
        $orderTotal = (float) ($row->order_total ?? 0);
        $discountAmount = (float) ($row->discount_amount ?? 0);
        $finalTotal = $orderTotal - $discountAmount;

        return ['redemption_date' => $row->redemption_date ?? '', 'coupon_code' => $row->coupon_code ?? '', 'user_email' => $row->user_email ?? 'Guest', 'order_id' => $row->order_id ? (int) $row->order_id : null, 'order_total' => $orderTotal, 'discount_amount' => $discountAmount, 'final_total' => $finalTotal, 'discount_type' => $row->discount_type ?? 'percentage'];
    }

    public function summarize(array $filters): array
    {
        $stats = DB::table('discount_lines')->join('orders', 'discount_lines.discountable_id', '=', 'orders.id')->where('discount_lines.discountable_type', 'like', '%Order%')->whereBetween('orders.created_at', [$filters['from'], $filters['to']])->whereNotNull('coupon_id')->select([DB::raw('COUNT(*) as total_redemptions'), DB::raw('SUM(discount_lines.value) as total_discount')])->first();

        return ['total_redemptions' => (int) ($stats->total_redemptions ?? 0), 'total_discount_given' => format_amount($stats->total_discount ?? 0)];
    }
}
