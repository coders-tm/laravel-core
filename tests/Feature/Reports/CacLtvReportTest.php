<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\Economics\CacLtvReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacLtvReportTest extends TestCase
{
    public function test_report_generates_without_binding_errors()
    {
        // Arrange: create minimal data across two months
        $from = Carbon::now()->subMonths(2)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Ensure clean tables
        DB::table('orders')->truncate();
        DB::table('subscriptions')->truncate();

        // Create subscriptions (new customers)
        DB::table('subscriptions')->insert([
            [
                'id' => 1,
                'user_id' => 1001,
                'plan_id' => 1,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(1)->toDateTimeString(),
                'canceled_at' => null,
                'expires_at' => null,
            ],
            [
                'id' => 2,
                'user_id' => 1002,
                'plan_id' => 1,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addMonth()->addDays(1)->toDateTimeString(),
                'canceled_at' => null,
                'expires_at' => null,
            ],
        ]);

        // Create orders (paid)
        DB::table('orders')->insert([
            [
                'id' => 1,
                'customer_id' => 1001,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 100.00,
                'created_at' => $from->copy()->addDays(2)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'customer_id' => 1002,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 50.00,
                'created_at' => $from->copy()->addMonth()->addDays(2)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new CacLtvReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert: report generates without errors
        $this->assertNotEmpty($result['data']);

        // Verify period labels are correct format
        foreach ($result['data'] as $row) {
            $this->assertIsString($row['period']);
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}$/',
                $row['period'],
                "Period should be YYYY-MM format, got: {$row['period']}"
            );

            // Verify no binding pollution
            $this->assertNotEquals('completed', $row['period']);
            $this->assertNotEquals('%Order', $row['period']);
            $this->assertIsNumeric($row['new_customers']);
            $this->assertIsNumeric($row['cac']);
            $this->assertIsNumeric($row['avg_ltv']);
        }

        // Verify at least one period has data
        $periodsWithCustomers = array_filter($result['data'], fn ($row) => $row['new_customers'] > 0);
        $this->assertNotEmpty($periodsWithCustomers, 'At least one period should have new customers');
    }

    public function test_period_labels_are_consistent()
    {
        // Arrange - Use current month to test period label formatting
        $from = Carbon::now()->startOfMonth();
        $to = Carbon::now()->endOfMonth()->addMonths(2);

        // Act
        $report = new CacLtvReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert: should have periods data
        $this->assertNotEmpty($result['data'], 'Should have period data');

        // Verify all periods match YYYY-MM format
        foreach ($result['data'] as $row) {
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}$/',
                $row['period'],
                "Period should be YYYY-MM format, got: {$row['period']}"
            );
        }

        // Verify no binding pollution from constants or LIKE patterns
        $actualPeriods = array_column($result['data'], 'period');
        $this->assertNotContains('completed', $actualPeriods, 'Periods should not contain status values');
        $this->assertNotContains('paid', $actualPeriods, 'Periods should not contain payment status values');
        $this->assertNotContains(Order::STATUS_PAID, $actualPeriods, 'Periods should not contain order status constants');
    }
}
