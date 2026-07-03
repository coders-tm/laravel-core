<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Shop\Order;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Plans\PlanRevenueBreakdownReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanRevenueBreakdownReportTest extends TestCase
{
    public function test_report_generates_revenue_breakdown_by_plan()
    {
        // Arrange: create plans with subscriptions and orders
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Ensure clean tables
        DB::table('orders')->truncate();
        DB::table('subscriptions')->truncate();

        // Create plans
        $plan1 = Plan::factory()->create(['label' => 'Basic Plan', 'price' => 29.00]);
        $plan2 = Plan::factory()->create(['label' => 'Premium Plan', 'price' => 99.00]);

        // Create subscriptions
        DB::table('subscriptions')->insert([
            [
                'id' => 1,
                'user_id' => 1001,
                'plan_id' => $plan1->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(1)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'user_id' => 1002,
                'plan_id' => $plan2->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(2)->toDateTimeString(),
            ],
        ]);

        // Create orders (paid)
        DB::table('orders')->insert([
            [
                'id' => 1,
                'customer_id' => 1001,
                'orderable_type' => 'Coderstm\\Models\\Subscription',
                'orderable_id' => 1,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 29.00,
                'discount_total' => 0.00,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'customer_id' => 1002,
                'orderable_type' => 'Coderstm\\Models\\Subscription',
                'orderable_id' => 2,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 99.00,
                'discount_total' => 10.00,
                'created_at' => $from->copy()->addDays(6)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new PlanRevenueBreakdownReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert: report generates without errors
        $this->assertNotEmpty($result['data']);

        // Verify data structure
        foreach ($result['data'] as $row) {
            $this->assertArrayHasKey('plan_id', $row);
            $this->assertArrayHasKey('plan_name', $row);
            $this->assertArrayHasKey('gross_revenue', $row);
            $this->assertArrayHasKey('discounts_applied', $row);
            $this->assertArrayHasKey('refunds', $row);
            $this->assertArrayHasKey('net_revenue', $row);
            $this->assertIsNumeric($row['gross_revenue']);
            $this->assertIsNumeric($row['net_revenue']);
        }
    }

    public function test_summary_calculates_total_revenue_breakdown()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('orders')->truncate();
        DB::table('subscriptions')->truncate();

        // Create subscription
        DB::table('subscriptions')->insert([
            'id' => 1,
            'user_id' => 1001,
            'plan_id' => 1,
            'type' => 'app',
            'status' => 'active',
            'quantity' => 1,
            'created_at' => $from->copy()->toDateTimeString(),
        ]);

        // Create orders
        DB::table('orders')->insert([
            [
                'customer_id' => 1001,
                'orderable_type' => 'Coderstm\\Models\\Subscription',
                'orderable_id' => 1,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 100.00,
                'discount_total' => 10.00,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new PlanRevenueBreakdownReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertArrayHasKey('total_gross_revenue', $summary);
        $this->assertArrayHasKey('total_discounts', $summary);
        $this->assertArrayHasKey('total_refunds', $summary);
        $this->assertArrayHasKey('total_net_revenue', $summary);
    }
}
