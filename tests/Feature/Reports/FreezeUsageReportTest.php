<?php

namespace Tests\Feature\Reports;

use Carbon\Carbon;
use Coderstm\Models\Subscription\Plan;
use Coderstm\Services\Reports\Subscriptions\FreezeUsageReport;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FreezeUsageReportTest extends TestCase
{
    public function test_report_generates_freeze_usage_data()
    {
        // Arrange
        $from = Carbon::now()->subMonths(1)->startOfMonth();
        $to = Carbon::now()->endOfMonth();

        DB::table('subscriptions')->truncate();

        $plan = Plan::factory()->create(['price' => 50.00]);

        DB::table('subscriptions')->insert([
            [
                'user_id' => 1001,
                'plan_id' => $plan->id,
                'type' => 'app',
                'status' => 'on_hold',
                'quantity' => 1,
                'created_at' => $from->copy()->toDateTimeString(),
            ],
        ]);

        // Act
        $report = new FreezeUsageReport;
        $filters = [
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'granularity' => 'monthly',
        ];

        $result = $report->paginate($report->validate($filters), 25, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
    }
}
