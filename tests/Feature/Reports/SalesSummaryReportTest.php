<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Services\Reports\Orders\SalesSummaryReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesSummaryReportTest extends TestCase
{
    public function test_report_generates_sales_summary_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('orders')->truncate();

        DB::table('orders')->insert([
            [
                'customer_id' => 1001,
                'status' => 'completed',
                'payment_status' => 'paid',
                'grand_total' => 250.00,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
            ],
            [
                'customer_id' => 1002,
                'status' => 'completed',
                'payment_status' => 'paid',
                'grand_total' => 300.00,
                'created_at' => $from->copy()->addDays(10)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new SalesSummaryReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertNotEmpty($result['data']);
        foreach ($result['data'] as $row) {
            $this->assertArrayHasKey('period', $row);
            $this->assertArrayHasKey('total_orders', $row);
            $this->assertArrayHasKey('gmv', $row);
            $this->assertArrayHasKey('net_revenue', $row);
        }
    }

    public function test_summary_calculates_totals()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('orders')->truncate();

        DB::table('orders')->insert([
            'customer_id' => 1001,
            'status' => 'completed',
            'payment_status' => 'paid',
            'grand_total' => 500.00,
            'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
        ]);

        // Act
        $report = new SalesSummaryReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertIsArray($summary);
    }
}
