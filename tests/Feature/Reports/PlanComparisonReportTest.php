<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Plans\PlanComparisonReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlanComparisonReportTest extends TestCase
{
    public function test_report_generates_plan_comparison_data()
    {
        // Arrange: create plans with subscriptions
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        // Ensure clean tables
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
                'canceled_at' => null,
            ],
            [
                'id' => 2,
                'user_id' => 1002,
                'plan_id' => $plan2->id,
                'type' => 'app',
                'status' => 'active',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(2)->toDateTimeString(),
                'canceled_at' => null,
            ],
            [
                'id' => 3,
                'user_id' => 1003,
                'plan_id' => $plan1->id,
                'type' => 'app',
                'status' => 'cancelled',
                'quantity' => 1,
                'created_at' => $from->copy()->addDays(3)->toDateTimeString(),
                'canceled_at' => $from->copy()->addDays(10)->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new PlanComparisonReport;
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
            $this->assertArrayHasKey('active_subscriptions', $row);
            $this->assertArrayHasKey('total_signups', $row);
            $this->assertArrayHasKey('churn_rate', $row);
            $this->assertIsNumeric($row['active_subscriptions']);
            $this->assertIsNumeric($row['churn_rate']);
        }
    }

    public function test_summary_aggregates_all_plans()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        // Create plans
        $plan1 = Plan::factory()->create(['label' => 'Plan A', 'price' => 50.00]);
        $plan2 = Plan::factory()->create(['label' => 'Plan B', 'price' => 100.00]);

        DB::table('subscriptions')->insert([
            ['user_id' => 1001, 'plan_id' => $plan1->id, 'type' => 'app', 'status' => 'active', 'quantity' => 1, 'created_at' => $from->copy()->toDateTimeString()],
            ['user_id' => 1002, 'plan_id' => $plan2->id, 'type' => 'app', 'status' => 'active', 'quantity' => 1, 'created_at' => $from->copy()->toDateTimeString()],
        ]);

        // Act
        $report = new PlanComparisonReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertArrayHasKey('total_plans', $summary);
        $this->assertArrayHasKey('total_active_subscriptions', $summary);
        $this->assertArrayHasKey('total_mrr', $summary);
    }
}
