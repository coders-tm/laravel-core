<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Shop\Order;
use Coderstm\Services\Reports\Economics\ClvReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Workbench\App\Models\User;

class ClvReportTest extends TestCase
{
    public function test_report_generates_with_customer_lifetime_value()
    {
        // Arrange: create users with orders
        $from = Carbon::now()->subMonths(6)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Ensure clean tables
        DB::table('orders')->truncate();

        // Create users using factory
        $user1 = User::factory()->create(['id' => 1001, 'email' => 'customer1@example.com']);
        $user2 = User::factory()->create(['id' => 1002, 'email' => 'customer2@example.com']);

        // Create orders (paid)
        DB::table('orders')->insert([
            [
                'id' => 1,
                'customer_id' => 1001,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 100.00,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
            ],
            [
                'id' => 2,
                'customer_id' => 1001,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 150.00,
                'created_at' => $from->copy()->addMonths(2)->toDateTimeString(),
            ],
            [
                'id' => 3,
                'customer_id' => 1002,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 200.00,
                'created_at' => $from->copy()->addMonth()->addDays(10)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new ClvReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert: report generates without errors
        $this->assertNotEmpty($result['data']);

        // Verify data structure
        foreach ($result['data'] as $row) {
            $this->assertArrayHasKey('user_id', $row);
            $this->assertArrayHasKey('user_email', $row);
            $this->assertArrayHasKey('total_revenue', $row);
            $this->assertArrayHasKey('avg_monthly_revenue', $row);
            $this->assertArrayHasKey('estimated_clv', $row);
            $this->assertArrayHasKey('order_count', $row);
            $this->assertIsNumeric($row['total_revenue']);
            $this->assertIsNumeric($row['estimated_clv']);
        }
    }

    public function test_summary_calculates_correctly()
    {
        // Arrange
        $from = Carbon::now()->subMonths(2)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('orders')->truncate();

        // Create user using factory
        $user = User::factory()->create(['id' => 1001, 'email' => 'customer@example.com']);

        // Create orders
        DB::table('orders')->insert([
            [
                'customer_id' => 1001,
                'status' => 'completed',
                'payment_status' => Order::STATUS_PAID,
                'grand_total' => 500.00,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new ClvReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertArrayHasKey('total_customers', $summary);
        $this->assertArrayHasKey('average_clv', $summary);
        $this->assertArrayHasKey('total_projected_clv', $summary);
        $this->assertGreaterThanOrEqual(1, $summary['total_customers']);
    }
}
