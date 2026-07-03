<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Coupon;
use Coderstm\Services\Reports\Coupons\CouponPerformanceReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CouponPerformanceReportTest extends TestCase
{
    public function test_report_generates_coupon_performance_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        $coupon = Coupon::factory()->create(['promotion_code' => 'SAVE20']);

        DB::table('discount_lines')->truncate();

        DB::table('discount_lines')->insert([
            [
                'coupon_id' => $coupon->id,
                'coupon_code' => 'SAVE20',
                'discountable_type' => 'Coderstm\Models\Order',
                'discountable_id' => 1,
                'value' => 20.00,
            ],
        ]);

        // Act
        $report = new CouponPerformanceReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}
