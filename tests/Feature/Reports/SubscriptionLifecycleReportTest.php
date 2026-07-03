<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Subscriptions\SubscriptionLifecycleReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubscriptionLifecycleReportTest extends TestCase
{
    public function test_report_generates_lifecycle_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(2)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 45.00]);

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
        $report = new SubscriptionLifecycleReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertNotEmpty($result['data']);
    }

    public function test_summary_calculates_lifecycle_metrics()
    {
        // Arrange
        $from = Carbon::now()->subMonth()->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 75.00]);

        DB::table('subscriptions')->insert([
            'user_id' => 1001,
            'plan_id' => $plan->id,
            'type' => 'app',
            'status' => 'active',
            'quantity' => 1,
            'created_at' => $from->copy()->addDays(5)->toDateTimeString(),
        ]);

        // Act
        $report = new SubscriptionLifecycleReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
        ];

        $summary = $report->summarize($report->validate($filters));

        // Assert
        $this->assertIsArray($summary);
    }
}
