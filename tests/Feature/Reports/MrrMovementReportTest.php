<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Revenue\MrrMovementReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MrrMovementReportTest extends TestCase
{
    public function test_report_generates_mrr_movement_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(2)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 40.00, 'interval' => 'month']);

        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
                'canceled_at' => null,
            ],
            [
                'user_id' => 1002,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'cancelled',
                'quantity' => 1,
                'created_at' => $from->copy()->toDateTimeString(),
                'canceled_at' => $from->copy()->addMonth()->addDays(10)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new MrrMovementReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertNotEmpty($result['data']);
    }

    public function test_summary_calculates_mrr_changes()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 100.00, 'interval' => 'month']);

        DB::table('subscriptions')->insert([
            'user_id' => 1001,
            'plan_id' => $plan->id,
            'type' => 'app',
            'status' => 'active',
            'quantity' => 1,
            'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
        ]);

        // Act
        $report = new MrrMovementReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertIsArray($summary);
    }
}
